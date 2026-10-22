<?php

namespace App\Notifications;

use App\Models\Order\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification : OrderStatusChangedNotification
 *
 * Notifie le client à chaque changement de statut de sa commande.
 *
 * Événements supportés :
 *   - accepted  : Le vendeur a accepté la commande
 *   - preparing : Le vendeur prépare le colis
 *   - shipped   : Le colis a été expédié (avec tracking)
 *   - cancelled : Le vendeur a annulé la commande
 *   - delivered : Le colis a été livré (bonus)
 *
 * Canaux :
 *   - Database (stockage en base pour l'app mobile)
 *   - Mail (email au client)
 *   - (FCM à ajouter en Phase 11+ quand Firebase sera branché)
 *
 * ⚠️ Queue :
 *   La notification est mise en queue pour ne pas bloquer la requête.
 *   → Le client reçoit la notif dans les secondes suivantes.
 *
 * Sécurité :
 *   - Ne contient AUCUNE donnée sensible (pas de mot de passe, pas de token)
 *   - L'URL de tracking est vérifiée avant inclusion
 *
 * @package App\Notifications
 */
class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Timeout en secondes pour le job en queue.
     */
    public int $timeout = 30;

    /**
     * Nombre de tentatives en cas d'échec.
     */
    public int $tries = 3;

    /**
     * La commande concernée.
     */
    private Order $order;

    /**
     * L'événement déclenché (accepted, preparing, shipped, cancelled, delivered).
     */
    private string $event;

    /**
     * L'ID du vendeur qui a déclenché l'événement.
     */
    private int $sellerId;

    /**
     * Données additionnelles (ex: tracking_number, carrier, reason...).
     */
    private array $extra;

    /**
     * Constructeur.
     */
    public function __construct(
        Order $order,
        string $event,
        int $sellerId,
        array $extra = []
    ) {
        $this->order = $order;
        $this->event = $event;
        $this->sellerId = $sellerId;
        $this->extra = $extra;
    }

    /**
     * Canaux de diffusion.
     *
     * @param object $notifiable
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Représentation en base de données.
     *
     * → Utilisée par l'app Flutter pour afficher les notifications in-app.
     * → Stockée dans la table `notifications` de Laravel.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'order_status_changed',
            'event'           => $this->event,
            'order_id'        => $this->order->id,
            'order_number'    => $this->order->order_number,
            'title'           => $this->getTitle(),
            'message'         => $this->getMessage(),
            'icon'            => $this->getIcon(),
            'color'           => $this->getColor(),
            'action_url'      => '/orders/' . $this->order->id,
            'extra'           => $this->buildExtraData(),
            'created_at'      => now()->toIso8601String(),
        ];
    }

    /**
     * Représentation pour l'email.
     *
     * @param object $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->getTitle() . ' - ' . $this->order->order_number)
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Client') . ',')
            ->line($this->getMessage())
            ->line('**Numéro de commande :** ' . $this->order->order_number);

        // Ajout du tracking si expédié
        if ($this->event === 'shipped' && !empty($this->extra['tracking_number'])) {
            $mail->line('**Numéro de suivi :** ' . $this->extra['tracking_number']);

            if (!empty($this->extra['carrier'])) {
                $mail->line('**Transporteur :** ' . $this->upperCarrier($this->extra['carrier']));
            }

            if (!empty($this->extra['tracking_url']) && $this->isValidUrl($this->extra['tracking_url'])) {
                $mail->action('Suivre mon colis', $this->extra['tracking_url']);
            }
        }

        // Ajout de la raison si annulé
        if ($this->event === 'cancelled' && !empty($this->extra['reason'])) {
            $mail->line('**Motif :** ' . $this->extra['reason']);
            $mail->line('Un remboursement sera effectué sous 3-5 jours ouvrés.');
        }

        $mail->line('Merci de votre confiance !')
             ->action('Voir ma commande', config('app.frontend_url', config('app.url')) . '/orders/' . $this->order->id)
             ->salutation('L\'équipe ShopPro');

        return $mail;
    }

    // ============================================
    // TITRES ET MESSAGES
    // ============================================

    /**
     * Titre de la notification selon l'événement.
     */
    private function getTitle(): string
    {
        return match ($this->event) {
            'accepted'  => '✅ Votre commande a été acceptée',
            'preparing' => '📦 Votre commande est en préparation',
            'shipped'   => '🚚 Votre commande a été expédiée',
            'delivered' => '🎉 Votre commande a été livrée',
            'cancelled' => '❌ Votre commande a été annulée',
            default     => '📬 Mise à jour de votre commande',
        };
    }

    /**
     * Message détaillé selon l'événement.
     */
    private function getMessage(): string
    {
        $sellerName = $this->getSellerShopName();
        $orderNumber = $this->order->order_number;

        return match ($this->event) {
            'accepted'  => "Le vendeur {$sellerName} a accepté votre commande {$orderNumber}. Elle sera bientôt en préparation.",
            'preparing' => "Votre commande {$orderNumber} est en cours de préparation par {$sellerName}.",
            'shipped'   => "Votre commande {$orderNumber} a été expédiée par {$sellerName}. Numéro de suivi : " . ($this->extra['tracking_number'] ?? 'N/A'),
            'delivered' => "Votre commande {$orderNumber} a été livrée. Nous espérons que tout vous convient !",
            'cancelled' => "Votre commande {$orderNumber} a été annulée par {$sellerName}. Raison : " . ($this->extra['reason'] ?? 'Non spécifiée'),
            default     => "Le statut de votre commande {$orderNumber} a été mis à jour.",
        };
    }

    /**
     * Icône Material Icons pour l'app Flutter.
     */
    private function getIcon(): string
    {
        return match ($this->event) {
            'accepted'  => 'check_circle',
            'preparing' => 'inventory_2',
            'shipped'   => 'local_shipping',
            'delivered' => 'celebration',
            'cancelled' => 'cancel',
            default     => 'notifications',
        };
    }

    /**
     * Couleur hexadécimale pour l'app Flutter.
     */
    private function getColor(): string
    {
        return match ($this->event) {
            'accepted'  => '#10B981', // success
            'preparing' => '#8B5CF6', // violet
            'shipped'   => '#3B82F6', // info
            'delivered' => '#10B981', // success
            'cancelled' => '#EF4444', // error
            default     => '#6B7280', // gray
        };
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Nom de la boutique du vendeur (pour personnaliser le message).
     */
    private function getSellerShopName(): string
    {
        $seller = \App\Models\User::find($this->sellerId);
        return $seller?->sellerProfile?->shop_name ?? 'notre partenaire';
    }

    /**
     * Construit les données additionnelles (payload pour l'app).
     */
    private function buildExtraData(): array
    {
        $data = [];

        if ($this->event === 'shipped') {
            $data['tracking_number'] = $this->extra['tracking_number'] ?? null;
            $data['carrier']         = $this->extra['carrier'] ?? null;
            $data['tracking_url']    = $this->extra['tracking_url'] ?? null;
        }

        if ($this->event === 'cancelled') {
            $data['reason'] = $this->extra['reason'] ?? null;
            $data['items_count'] = $this->extra['items_count'] ?? 0;
        }

        return $data;
    }

    /**
     * Met en majuscules le nom du transporteur.
     */
    private function upperCarrier(string $carrier): string
    {
        return strtoupper(str_replace('_', ' ', $carrier));
    }

    /**
     * Vérifie qu'une URL est valide et sécurisée.
     */
    private function isValidUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Seulement HTTPS autorisé (sécurité)
        return str_starts_with($url, 'https://');
    }
}