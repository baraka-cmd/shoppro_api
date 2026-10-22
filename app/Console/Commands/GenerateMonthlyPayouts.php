<?php

namespace App\Console\Commands;

use App\Models\Seller\SellerProfile;
use App\Models\Seller\SellerPayout;
use App\Models\Seller\SellerTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Command Artisan : GenerateMonthlyPayouts
 *
 * Génère automatiquement les payouts mensuels pour les vendeurs éligibles.
 *
 * ⚠️ OBJECTIF : Automatiser le versement des gains aux vendeurs.
 *
 * ⚠️ RÈGLES CRITIQUES :
 *   1. SEULEMENT les vendeurs avec :
 *      - KYC validé
 *      - Compte actif
 *      - balance >= seuil minimum (5000 FCFA par défaut)
 *      - Aucun payout en cours (pending/processing)
 *      - Méthode de payout configurée
 *   2. Les payouts sont créés en statut 'pending' → un admin devra les valider
 *   3. Le solde est débité SEULEMENT quand l'admin passe le payout en 'completed'
 *   4. Chaque opération est tracée dans SellerTransaction
 *
 * ⚠️ SÉCURITÉ :
 *   - `withoutOverlapping()` : évite les exécutions simultanées
 *   - `onOneServer()` : si plusieurs serveurs, un seul exécute
 *   - Logs détaillés pour audit
 *   - Dry-run disponible pour tester
 *
 * Usage :
 *   php artisan seller:generate-monthly-payouts           # Génère les payouts
 *   php artisan seller:generate-monthly-payouts --dry-run # Simule sans créer
 *   php artisan seller:generate-monthly-payouts --min=10000 # Seuil custom
 *
 * @package App\Console\Commands
 */
class GenerateMonthlyPayouts extends Command
{
    /**
     * Nom et signature de la commande.
     *
     * @var string
     */
    protected $signature = 'seller:generate-monthly-payouts
                            {--dry-run : Simuler sans créer les payouts}
                            {--min=5000 : Seuil minimum de balance pour générer un payout}
                            {--seller-id= : Générer pour un vendeur spécifique}';

    /**
     * Description de la commande.
     *
     * @var string
     */
    protected $description = 'Génère les payouts mensuels pour les vendeurs éligibles';

    /**
     * Exécution de la commande.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $minBalance = (float) $this->option('min');
        $sellerId = $this->option('seller-id');

        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  GÉNÉRATION DES PAYOUTS MENSUELS VENDEURS');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line("Mode      : " . ($dryRun ? '🔍 DRY-RUN (simulation)' : '🚀 EXÉCUTION RÉELLE'));
        $this->line("Seuil min : " . number_format($minBalance, 0, ',', ' ') . ' FCFA');
        $this->line("Vendeur   : " . ($sellerId ? "ID #{$sellerId}" : 'Tous'));
        $this->line('');

        try {
            // ============================================
            // 1. RÉCUPÉRER LES VENDEURS ÉLIGIBLES
            // ============================================
            $query = SellerProfile::query()
                ->where('is_active', true)
                ->where('kyc_status', 'verified')
                ->where('balance', '>=', $minBalance)
                ->whereNotNull('payout_method')
                ->whereNotNull('payout_account');

            if ($sellerId) {
                $query->where('user_id', (int) $sellerId);
            }

            $eligibleSellers = $query->get();

            if ($eligibleSellers->isEmpty()) {
                $this->warn('⚠️  Aucun vendeur éligible trouvé.');
                return Command::SUCCESS;
            }

            $this->info("📊 {$eligibleSellers->count()} vendeur(s) éligible(s) trouvé(s).");
            $this->line('');

            // ============================================
            // 2. TRAITER CHAQUE VENDEUR
            // ============================================
            $created = 0;
            $skipped = 0;
            $errors = 0;
            $totalAmount = 0;

            $bar = $this->output->createProgressBar($eligibleSellers->count());
            $bar->start();

            foreach ($eligibleSellers as $seller) {
                try {
                    // Vérifier qu'il n'y a pas de payout en cours
                    $hasActivePayout = SellerPayout::where('seller_id', $seller->user_id)
                        ->whereIn('status', ['pending', 'processing'])
                        ->exists();

                    if ($hasActivePayout) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Créer le payout
                    if (!$dryRun) {
                        DB::beginTransaction();

                        $payout = SellerPayout::create([
                            'reference'    => $this->generateReference(),
                            'seller_id'    => $seller->user_id,
                            'amount'       => $seller->balance,
                            'fee'          => 0,
                            'net_amount'   => $seller->balance,
                            'currency'     => 'FCFA',
                            'method'       => $seller->payout_method,
                            'account'      => $seller->payout_account,
                            'account_name' => $seller->user?->name ?? 'Vendeur',
                            'status'       => 'pending',
                            'requested_at' => now(),
                            'note'         => 'Généré automatiquement (mensuel)',
                        ]);

                        // Créer la transaction associée
                        SellerTransaction::create([
                            'reference'         => 'TXN-' . $payout->reference,
                            'seller_id'         => $seller->user_id,
                            'type'              => 'payout',
                            'direction'         => 'debit',
                            'amount'            => $seller->balance,
                            'commission_amount' => 0,
                            'net_amount'        => $seller->balance,
                            'currency'          => 'FCFA',
                            'description'       => 'Payout automatique mensuel #' . $payout->reference,
                            'payout_id'         => $payout->id,
                        ]);

                        DB::commit();

                        Log::info('Monthly payout generated', [
                            'payout_id'    => $payout->id,
                            'seller_id'    => $seller->user_id,
                            'amount'       => $seller->balance,
                            'method'       => $seller->payout_method,
                        ]);
                    }

                    $created++;
                    $totalAmount += $seller->balance;
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $errors++;
                    $this->newLine();
                    $this->error("  ❌ Erreur pour vendeur #{$seller->user_id}: {$e->getMessage()}");
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // ============================================
            // 3. RÉSUMÉ
            // ============================================
            $this->info('═══════════════════════════════════════════════════════');
            $this->info('  RÉSUMÉ');
            $this->info('═══════════════════════════════════════════════════════');
            $this->line("✅ Payouts " . ($dryRun ? 'simulés' : 'créés') . "  : {$created}");
            $this->line("⏭️  Ignorés (déjà actif) : {$skipped}");
            $this->line("❌ Erreurs                : {$errors}");
            $this->line("💰 Montant total          : " . number_format($totalAmount, 0, ',', ' ') . ' FCFA');
            $this->info('═══════════════════════════════════════════════════════');

            if ($dryRun) {
                $this->newLine();
                $this->warn('🔍 Mode dry-run : aucun payout réel n\'a été créé.');
                $this->warn('   Pour créer les payouts, retirez l\'option --dry-run.');
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Erreur fatale : ' . $e->getMessage());
            Log::error('GenerateMonthlyPayouts failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Génère une référence unique de payout.
     */
    private function generateReference(): string
    {
        $prefix = 'PAYOUT';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . '-' . $date . '-' . $random;
    }
}