<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment\Payment;
use App\Models\Payment\Transaction;

class TransactionsSeeder extends Seeder
{
    /**
     * Seed des transactions
     */
    public function run(): void
    {
        $payments = Payment::where('status', 'completed')->take(10)->get();

        foreach ($payments as $payment) {
            Transaction::create([
                'transaction_number' => Transaction::generateTransactionNumber(),
                'user_id' => $payment->user_id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'fee' => $payment->fee,
                'net_amount' => $payment->amount - $payment->fee,
                'currency' => $payment->currency,
                'type' => 'payment',
                'status' => 'completed',
                'payment_method' => $payment->method,
                'payment_gateway' => $payment->method === 'card' ? 'stripe' : ($payment->method === 'mobile_money' ? 'mtn' : 'bank'),
                'gateway_transaction_id' => $payment->transaction_id,
                'gateway_reference' => $payment->gateway_reference,
                'description' => 'Paiement de la commande ' . $payment->order->order_number,
                'completed_at' => $payment->paid_at,
            ]);
        }
    }
}