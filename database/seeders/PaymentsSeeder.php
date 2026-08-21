<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order\Order;
use App\Models\Payment\Payment;

class PaymentsSeeder extends Seeder
{
    /**
     * Seed des paiements
     */
    public function run(): void
    {
        $orders = Order::whereIn('payment_status', ['paid', 'pending'])
                       ->take(15)
                       ->get();

        foreach ($orders as $index => $order) {
            $isPaid = $order->payment_status === 'paid';
            $methods = ['cash_on_delivery', 'mobile_money', 'card', 'bank_transfer'];
            $method = $methods[rand(0, count($methods) - 1)];
            
            Payment::create([
                'payment_number' => Payment::generatePaymentNumber(),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->total,
                'fee' => $isPaid ? rand(50, 500) : 0,
                'total_amount' => $isPaid ? $order->total + rand(50, 500) : $order->total,
                'currency' => $order->currency,
                'method' => $method,
                'status' => $isPaid ? 'completed' : 'pending',
                'transaction_id' => $isPaid ? 'TXN' . rand(100000, 999999) : null,
                'gateway_reference' => $isPaid ? 'GW' . rand(100000, 999999) : null,
                'paid_at' => $isPaid ? now()->subDays(rand(1, 15)) : null,
            ]);
        }
    }
}