<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\User\UserAddress;
use App\Models\Order\Order;

class OrdersSeeder extends Seeder
{
    /**
     * Seed des commandes
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        $paymentStatuses = ['pending', 'paid', 'paid', 'paid', 'paid', 'pending'];

        foreach ($users as $userIndex => $user) {
            $address = UserAddress::where('user_id', $user->id)->first();
            
            if (!$address) {
                continue;
            }

            // Chaque utilisateur a 2-5 commandes
            $orderCount = rand(2, 5);
            
            for ($i = 0; $i < $orderCount; $i++) {
                $statusIndex = rand(0, count($statuses) - 1);
                $subtotal = rand(20000, 500000);
                $shippingFee = 2000;
                $tax = $subtotal * 0.1925;
                $discount = rand(0, 10000);
                $total = $subtotal + $shippingFee + $tax - $discount;
                
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $user->id,
                    'shipping_address_id' => $address->id,
                    'billing_address_id' => $address->id,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'shipping_fee' => $shippingFee,
                    'tax' => $tax,
                    'total' => $total,
                    'status' => $statuses[$statusIndex],
                    'payment_status' => $paymentStatuses[$statusIndex],
                    'shipping_method' => 'standard',
                    'tracking_number' => $statusIndex >= 3 ? 'TRK' . rand(100000, 999999) : null,
                    'estimated_delivery_date' => now()->addDays(rand(3, 14)),
                    'currency' => 'FCFA',
                    'customer_note' => $i % 2 === 0 ? 'Merci de bien emballer' : null,
                ]);

                // Ajouter l'historique de statut
                $order->statusHistory()->create([
                    'order_id' => $order->id,
                    'old_status' => null,
                    'new_status' => 'pending',
                    'note' => 'Commande créée',
                ]);

                if ($statusIndex > 0) {
                    $order->statusHistory()->create([
                        'order_id' => $order->id,
                        'old_status' => 'pending',
                        'new_status' => $statuses[$statusIndex],
                        'note' => 'Statut mis à jour',
                    ]);
                }
            }
        }
    }
}