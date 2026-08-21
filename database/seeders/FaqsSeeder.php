<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Support\Faq;

class FaqsSeeder extends Seeder
{
    /**
     * Seed des FAQs
     */
    public function run(): void
    {
        $faqs = [
            // Général
            [
                'question' => 'Comment créer un compte sur ShopPro ?',
                'answer' => 'Cliquez sur "S\'inscrire" en haut de la page, remplissez le formulaire avec vos informations, puis validez. Un email de confirmation vous sera envoyé.',
                'category' => 'general',
                'position' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'question' => 'Comment suivre ma commande ?',
                'answer' => 'Connectez-vous à votre compte, allez dans "Mes commandes", sélectionnez la commande concernée et cliquez sur "Suivre". Le statut en temps réel s\'affichera.',
                'category' => 'orders',
                'position' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            
            // Commandes
            [
                'question' => 'Puis-je modifier ou annuler ma commande ?',
                'answer' => 'Vous pouvez annuler votre commande tant qu\'elle n\'a pas été expédiée. Pour les modifications, contactez notre support dans les 24h.',
                'category' => 'orders',
                'position' => 2,
                'is_featured' => false,
                'is_active' => true,
            ],
            
            // Livraison
            [
                'question' => 'Quels sont les délais de livraison ?',
                'answer' => 'Les délais varient selon la méthode choisie : Express (1-2 jours), Standard (3-7 jours), Économique (7-14 jours).',
                'category' => 'shipping',
                'position' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'question' => 'La livraison est-elle gratuite ?',
                'answer' => 'La livraison est gratuite pour toute commande supérieure à 50 000 FCFA. En dessous, des frais de 2 000 FCFA s\'appliquent.',
                'category' => 'shipping',
                'position' => 2,
                'is_featured' => false,
                'is_active' => true,
            ],
            
            // Paiements
            [
                'question' => 'Quels moyens de paiement acceptez-vous ?',
                'answer' => 'Nous acceptons : Paiement à la livraison, Mobile Money (MTN, Orange), Carte bancaire et Virement bancaire.',
                'category' => 'payments',
                'position' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'question' => 'Le paiement est-il sécurisé ?',
                'answer' => 'Oui, tous les paiements sont sécurisés avec un cryptage SSL et des protocoles de sécurité avancés.',
                'category' => 'payments',
                'position' => 2,
                'is_featured' => false,
                'is_active' => true,
            ],
            
            // Retours
            [
                'question' => 'Comment retourner un produit ?',
                'answer' => 'Vous disposez de 7 jours après réception pour retourner un produit. Connectez-vous, sélectionnez la commande et cliquez sur "Retourner".',
                'category' => 'returns',
                'position' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            
            // Compte
            [
                'question' => 'Comment modifier mon mot de passe ?',
                'answer' => 'Allez dans "Mon profil" > "Sécurité" > "Modifier le mot de passe". Entrez votre mot de passe actuel puis le nouveau.',
                'category' => 'account',
                'position' => 1,
                'is_featured' => false,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}