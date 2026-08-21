<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment\Payment;
use App\Models\Payment\Refund;

class RefundsSeeder extends Seeder
{
    /**
     * Seed des remboursements
     */
    public function run(): void
    {
        $payments = Payment::where('status', 'refunded')
                            ->orWhere('status', 'partially_refunded')
                            ->take(5)
                            ->get();

        // Si pas de paiements remboursés, en créer
        if ($payments->count() === 0) {
            $payments = Payment::where('status', 'completed')->take(3)->get();
        }

        $reasons = [
            'Produit défectueux',
            'Produit non conforme',
            'Livraison trop tardive',
            'Erreur de commande',
            'Doublon de paiement',
        ];

        foreach ($payments as $index => $payment) {
            $isFull = $index % 2 === 0;
            $amount = $isFull ? $payment->amount : $payment->amount * 0.5;
            
            Refund::create([
                'refund_number' => Refund::generateRefundNumber(),
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'user_id' => $payment->user_id,
                'amount' => $amount,
                'fee_refunded' => $payment->fee,
                'total_refunded' => $amount + $payment->fee,
                'type' => $isFull ? 'full' : 'partial',
                'status' => 'completed',
                'reason' => $reasons[$index % count($reasons)],
                'note' => 'Remboursement effectué',
                'gateway_refund_id' => 'RF' . rand(100000, 999999),
                'processed_at' => now()->subDays(rand(1, 10)),
                'completed_at' => now()->subDays(rand(1, 10)),
            ]);
        }
    }
}