<?php

namespace Database\Seeders;

use App\Models\Support\Faq;
use App\Models\Support\FaqCategory;
use Illuminate\Database\Seeder;

class FaqsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les catégories
        $commandesCategory = FaqCategory::where('slug', 'commandes')->first();
        $paiementsCategory = FaqCategory::where('slug', 'paiements')->first();
        $livraisonCategory = FaqCategory::where('slug', 'livraison')->first();
        $retoursCategory = FaqCategory::where('slug', 'retours-remboursements')->first();
        $compteCategory = FaqCategory::where('slug', 'compte-securite')->first();

        $faqs = [
            // ============================================
            // COMMANDES
            // ============================================
            [
                'faq_category_id' => $commandesCategory?->id,
                'question' => 'Comment suivre ma commande ?',
                'answer' => "Pour suivre votre commande :\n\n1. Ouvrez l'application ShopPro\n2. Allez dans votre profil\n3. Sélectionnez « Mes commandes »\n4. Ouvrez la commande concernée\n5. Consultez le statut en temps réel\n\nLes statuts possibles sont :\n• Commande confirmée\n• En préparation\n• Expédiée\n• En livraison\n• Livrée",
                'slug' => 'comment-suivre-ma-commande',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'faq_category_id' => $commandesCategory?->id,
                'question' => 'Puis-je annuler ma commande ?',
                'answer' => "Oui, vous pouvez annuler votre commande si elle n'a pas encore été expédiée.\n\nPour annuler :\n1. Ouvrez « Mes commandes »\n2. Sélectionnez la commande concernée\n3. Cliquez sur « Annuler la commande »\n4. Choisissez le motif d'annulation\n5. Confirmez l'annulation\n\nSi la commande est déjà expédiée, vous pourrez demander un retour après réception.",
                'slug' => 'puis-je-annuler-ma-commande',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'faq_category_id' => $commandesCategory?->id,
                'question' => 'Comment modifier ma commande après validation ?',
                'answer' => "Une fois la commande validée, il n'est pas possible de la modifier directement.\n\nSi vous souhaitez changer un article ou la quantité :\n1. Annulez la commande actuelle (si possible)\n2. Passez une nouvelle commande\n\nPour toute assistance, contactez notre support avec votre numéro de commande.",
                'slug' => 'comment-modifier-ma-commande',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ],

            // ============================================
            // PAIEMENTS
            // ============================================
            [
                'faq_category_id' => $paiementsCategory?->id,
                'question' => 'Quels moyens de paiement acceptez-vous ?',
                'answer' => "ShopPro accepte les moyens de paiement suivants :\n\n1. 💵 Paiement à la livraison (espèces)\n2. 📱 Airtel Money\n3. 📱 Orange Money\n4. 💳 Carte bancaire (bientôt disponible)\n\nPour le Mobile Money :\n• Airtel Money : composez *101#\n• Orange Money : composez *144#\n\nVous recevrez les instructions de paiement après confirmation de votre commande.",
                'slug' => 'quels-moyens-de-paiement',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'faq_category_id' => $paiementsCategory?->id,
                'question' => 'Le paiement est-il sécurisé ?',
                'answer' => "Oui, le paiement est 100% sécurisé.\n\nSécurité ShopPro :\n• Nous ne stockons jamais vos PIN Mobile Money\n• Les transactions sont traitées par les opérateurs officiels (Airtel, Orange)\n• Vos données bancaires ne sont jamais enregistrées dans l'application\n• Toutes les transactions sont vérifiées avant confirmation\n\nPour toute question, notre support est disponible 7j/7.",
                'slug' => 'le-paiement-est-il-securise',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'faq_category_id' => $paiementsCategory?->id,
                'question' => 'Comment obtenir un remboursement ?',
                'answer' => "Pour obtenir un remboursement :\n\n1. Contactez notre support via « Nous contacter »\n2. Indiquez votre numéro de commande\n3. Précisez le motif du remboursement\n4. Notre équipe analysera votre demande sous 48h\n\nDélais de remboursement :\n• Mobile Money : 2-5 jours ouvrés\n• Espèces : 3-7 jours ouvrés\n\nLe remboursement est effectué sur le même moyen de paiement utilisé lors de l'achat.",
                'slug' => 'comment-obtenir-un-remboursement',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 3,
            ],

            // ============================================
            // LIVRAISON
            // ============================================
            [
                'faq_category_id' => $livraisonCategory?->id,
                'question' => 'Quels sont les délais de livraison ?',
                'answer' => "Délais de livraison selon la zone :\n\n📍 Goma (ville) : 2-24 heures\n📍 Goma (périphérie) : 1-2 jours\n📍 Autres villes du Nord-Kivu : 2-4 jours\n📍 Reste de la RDC : 3-7 jours\n\nNote : Les délais peuvent varier selon la disponibilité des produits et les conditions météorologiques.",
                'slug' => 'quels-sont-les-delais-de-livraison',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'faq_category_id' => $livraisonCategory?->id,
                'question' => 'La livraison est-elle gratuite ?',
                'answer' => "Oui ! La livraison est gratuite pour toute commande de 50 000 FCFA ou plus.\n\nFrais de livraison standard :\n• Livraison Standard : 2 000 FCFA (3-7 jours)\n• Livraison Express : 5 000 FCFA (1-2 jours)\n• Point Relais : 1 000 FCFA (2-5 jours)\n\nAstuce : Ajoutez plus de produits à votre panier pour atteindre le seuil de livraison gratuite !",
                'slug' => 'la-livraison-est-elle-gratuite',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'faq_category_id' => $livraisonCategory?->id,
                'question' => 'Livrez-vous partout en RDC ?',
                'answer' => "Oui, ShopPro livre dans toute la République Démocratique du Congo.\n\nZones desservies :\n• Nord-Kivu (Goma, Beni, Butembo)\n• Sud-Kivu (Bukavu, Uvira)\n• Kinshasa\n• Lubumbashi\n• Kisangani\n• Et bien d'autres villes\n\nPour connaître les délais exacts pour votre ville, contactez notre support.",
                'slug' => 'livrez-vous-partout-en-rdc',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ],

            // ============================================
            // RETOURS & REMBOURSEMENTS
            // ============================================
            [
                'faq_category_id' => $retoursCategory?->id,
                'question' => 'Comment retourner un produit ?',
                'answer' => "Pour retourner un produit :\n\n1. Vous disposez de 7 jours après réception\n2. Le produit doit être dans son emballage d'origine\n3. Contactez notre support avec votre numéro de commande\n4. Précisez le motif du retour\n5. Notre équipe vous indiquera la procédure\n\nProduits non retournables :\n• Produits d'hygiène ouverts\n• Produits endommagés par l'utilisateur\n• Produits personnalisés",
                'slug' => 'comment-retourner-un-produit',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'faq_category_id' => $retoursCategory?->id,
                'question' => 'Quelles sont les conditions de remboursement ?',
                'answer' => "Conditions de remboursement :\n\n1. Produit défectueux à la réception\n2. Produit incorrect reçu\n3. Produit endommagé pendant la livraison\n4. Produit non conforme à la description\n\nLe remboursement est effectué après vérification par notre équipe dans un délai de 2-5 jours ouvrés.",
                'slug' => 'conditions-de-remboursement',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 2,
            ],

            // ============================================
            // COMPTE & SÉCURITÉ
            // ============================================
            [
                'faq_category_id' => $compteCategory?->id,
                'question' => 'Comment modifier mon mot de passe ?',
                'answer' => "Pour modifier votre mot de passe :\n\n1. Ouvrez votre profil\n2. Sélectionnez « Changer mot de passe »\n3. Entrez votre mot de passe actuel\n4. Choisissez un nouveau mot de passe fort\n5. Confirmez le nouveau mot de passe\n\nConseils :\n• Au moins 8 caractères\n• Lettres majuscules et minuscules\n• Chiffres\n• Caractères spéciaux",
                'slug' => 'comment-modifier-mon-mot-de-passe',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'faq_category_id' => $compteCategory?->id,
                'question' => 'Comment mettre à jour mon adresse de livraison ?',
                'answer' => "Pour mettre à jour votre adresse :\n\n1. Allez dans « Mes adresses » depuis votre profil\n2. Cliquez sur « Ajouter une adresse » ou modifiez une adresse existante\n3. Remplissez les informations (province, ville, commune, quartier...)\n4. Ajoutez un point de repère pour aider le livreur\n5. Définissez l'adresse par défaut\n\nAstuce : Un point de repère précis (ex : « Près de l'église X ») facilite la livraison.",
                'slug' => 'comment-modifier-mon-adresse',
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'faq_category_id' => $compteCategory?->id,
                'question' => 'Comment supprimer mon compte ?',
                'answer' => "Pour supprimer votre compte :\n\n1. Contactez notre support via « Nous contacter »\n2. Indiquez « Suppression de compte » comme sujet\n3. Précisez votre email et nom complet\n4. Notre équipe traitera votre demande sous 48h\n\nNote : La suppression est définitive. Toutes vos données (commandes, favoris, adresses) seront supprimées conformément à notre politique de confidentialité.",
                'slug' => 'comment-supprimer-mon-compte',
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['slug' => $faq['slug']],
                $faq
            );
        }
    }
}