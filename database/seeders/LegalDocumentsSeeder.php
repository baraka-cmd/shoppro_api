<?php

namespace Database\Seeders;

use App\Models\Legal\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            [
                'title' => 'Conditions d\'utilisation',
                'slug' => 'conditions-utilisation',
                'type' => 'terms_conditions',
                'content' => "# Conditions d'utilisation de ShopPro\n\n**Dernière mise à jour :** 1er septembre 2026\n\n## 1. Acceptation des conditions\n\nEn utilisant l'application ShopPro, vous acceptez les présentes conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser l'application.\n\n## 2. Description du service\n\nShopPro est une application e-commerce qui permet aux utilisateurs d'acheter des produits en ligne et de se les faire livrer en République Démocratique du Congo.\n\n## 3. Création du compte\n\nPour utiliser ShopPro, vous devez :\n- Avoir au moins 18 ans\n- Fournir des informations exactes\n- Maintenir la confidentialité de votre mot de passe\n- Mettre à jour vos informations en cas de changement\n\n## 4. Commandes\n\nToute commande passée sur ShopPro constitue une offre d'achat. ShopPro se réserve le droit de refuser ou d'annuler une commande en cas de suspicion de fraude.\n\n## 5. Prix et paiements\n\nLes prix sont affichés en Francs Congolais (CDF) ou en Dollars US (USD). Les frais de livraison sont calculés séparément. Les paiements peuvent être effectués par Mobile Money, carte bancaire ou à la livraison.\n\n## 6. Livraison\n\nShopPro livre dans toute la RDC. Les délais varient selon la zone géographique. La livraison est gratuite pour toute commande de 50 000 FCFA ou plus.\n\n## 7. Retours et remboursements\n\nVous disposez de 7 jours après réception pour retourner un produit. Le produit doit être dans son emballage d'origine et non utilisé.\n\n## 8. Responsabilités de l'utilisateur\n\nL'utilisateur s'engage à :\n- Fournir des informations exactes\n- Ne pas utiliser l'application à des fins illégales\n- Ne pas tenter de perturber le fonctionnement du service\n- Respecter les droits de propriété intellectuelle\n\n## 9. Propriété intellectuelle\n\nTous les contenus de l'application (logo, textes, images) sont la propriété de ShopPro et ne peuvent être reproduits sans autorisation.\n\n## 10. Disponibilité du service\n\nShopPro s'efforce de maintenir le service disponible 24h/24 et 7j/7. Des interruptions peuvent survenir pour maintenance ou mise à jour.\n\n## 11. Limitation de responsabilité\n\nShopPro ne peut être tenu responsable des dommages indirects résultant de l'utilisation du service.\n\n## 12. Modification des conditions\n\nShopPro se réserve le droit de modifier ces conditions à tout moment. Les modifications seront publiées dans l'application.\n\n## 13. Résiliation\n\nShopPro peut suspendre ou résilier votre compte en cas de violation de ces conditions.\n\n## 14. Droit applicable\n\nCes conditions sont régies par le droit congolais.\n\n## 15. Contact\n\nPour toute question :\n- Email : support@shoppro.cd\n- Téléphone : +243 99 000 00 00\n- Adresse : Goma, Nord-Kivu, RDC",
                'version' => '1.0.0',
                'published_at' => '2026-09-01 00:00:00',
                'is_active' => true,
            ],
            [
                'title' => 'Politique de confidentialité',
                'slug' => 'politique-confidentialite',
                'type' => 'privacy_policy',
                'content' => "# Politique de confidentialité de ShopPro\n\n**Dernière mise à jour :** 1er septembre 2026\n\n## 1. Collecte des données\n\nNous collectons les données suivantes :\n- Nom complet\n- Adresse email\n- Numéro de téléphone\n- Adresse de livraison\n- Historique des commandes\n- Photo de profil (optionnelle)\n\n## 2. Utilisation des données\n\nVos données sont utilisées pour :\n- Traiter vos commandes\n- Vous livrer les produits\n- Vous envoyer des notifications\n- Améliorer nos services\n- Personnaliser votre expérience\n\n## 3. Protection des données\n\nNous mettons en place des mesures de sécurité :\n- Cryptage des données sensibles\n- Accès limité aux données\n- Surveillance des accès\n- Mises à jour de sécurité régulières\n\n## 4. Partage des données\n\nVos données ne sont jamais vendues à des tiers. Elles peuvent être partagées avec :\n- Prestataires de paiement (Airtel, Orange)\n- Services de livraison\n- Autorités légales si requis par la loi\n\n## 5. Durée de conservation\n\nVos données sont conservées pendant la durée de votre compte. Vous pouvez demander leur suppression à tout moment.\n\n## 6. Vos droits\n\nVous avez le droit de :\n- Accéder à vos données\n- Modifier vos données\n- Supprimer vos données\n- Retirer votre consentement\n\n## 7. Sécurité\n\nNous ne stockons jamais :\n- Vos PIN Mobile Money\n- Vos mots de passe en clair\n- Vos numéros de carte bancaire\n\n## 8. Contact\n\nPour toute question sur vos données :\n- Email : privacy@shoppro.cd\n- Téléphone : +243 99 000 00 00",
                'version' => '1.0.0',
                'published_at' => '2026-09-01 00:00:00',
                'is_active' => true,
            ],
            [
                'title' => 'Politique de remboursement',
                'slug' => 'politique-remboursement',
                'type' => 'refund_policy',
                'content' => "# Politique de remboursement de ShopPro\n\n**Dernière mise à jour :** 1er septembre 2026\n\n## 1. Conditions de remboursement\n\nVous pouvez demander un remboursement dans les cas suivants :\n- Produit défectueux\n- Produit incorrect reçu\n- Produit endommagé pendant la livraison\n- Produit non conforme à la description\n\n## 2. Délai de demande\n\nVous disposez de 7 jours après réception pour demander un remboursement.\n\n## 3. Délais de traitement\n\n- Vérification : 48h\n- Remboursement Mobile Money : 2-5 jours ouvrés\n- Remboursement espèces : 3-7 jours ouvrés\n\n## 4. Produits non remboursables\n\n- Produits d'hygiène ouverts\n- Produits endommagés par l'utilisateur\n- Produits personnalisés\n- Produits soldés (sauf défaut)\n\n## 5. Contact\n\nEmail : support@shoppro.cd",
                'version' => '1.0.0',
                'published_at' => '2026-09-01 00:00:00',
                'is_active' => true,
            ],
        ];

        foreach ($documents as $document) {
            LegalDocument::updateOrCreate(
                ['slug' => $document['slug']],
                $document
            );
        }
    }
}