<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $order->order_number }}</title>
    <style>
        /* ============================================ */
        /* RESET                                        */
        /* ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #2C3E50;
            line-height: 1.5;
            padding: 30px;
        }

        /* ============================================ */
        /* HEADER                                       */
        /* ============================================ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2C3E50;
        }

        .header-left {
            flex: 1;
        }

        .header-right {
            text-align: right;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2C3E50;
            letter-spacing: 1px;
        }

        .logo span {
            color: #E67E22;
        }

        .company-info {
            margin-top: 8px;
            font-size: 10px;
            color: #7F8C8D;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2C3E50;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 12px;
            color: #E67E22;
            font-weight: bold;
        }

        /* ============================================ */
        /* INFO BLOCKS                                  */
        /* ============================================ */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 15px;
        }

        .info-block {
            flex: 1;
            padding: 12px 15px;
            background-color: #F5F6FA;
            border-radius: 6px;
            border-left: 4px solid #E67E22;
        }

        .info-block h3 {
            font-size: 10px;
            text-transform: uppercase;
            color: #7F8C8D;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }

        .info-block p {
            font-size: 11px;
            margin-bottom: 2px;
            color: #2C3E50;
        }

        .info-block strong {
            font-weight: bold;
        }

        /* ============================================ */
        /* TABLE ARTICLES                               */
        /* ============================================ */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead {
            background-color: #2C3E50;
            color: white;
        }

        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }

        .items-table th.center {
            text-align: center;
        }

        .items-table th.right {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #E5E5E5;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #F9FAFB;
        }

        .items-table td {
            padding: 10px 8px;
            font-size: 11px;
            vertical-align: middle;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .product-name {
            font-weight: bold;
            color: #2C3E50;
        }

        .product-sku {
            font-size: 9px;
            color: #7F8C8D;
            margin-top: 2px;
        }

        .product-variant {
            font-size: 9px;
            color: #E67E22;
            margin-top: 2px;
        }

        /* ============================================ */
        /* TOTALS                                       */
        /* ============================================ */
        .totals {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
        }

        .totals-table {
            width: 300px;
        }

        .totals-table tr {
            border: none;
        }

        .totals-table td {
            padding: 6px 10px;
            font-size: 11px;
        }

        .totals-table td.label {
            text-align: right;
            color: #7F8C8D;
        }

        .totals-table td.value {
            text-align: right;
            font-weight: bold;
            color: #2C3E50;
        }

        .totals-table tr.total-row td {
            background-color: #2C3E50;
            color: white;
            font-size: 13px;
            padding: 10px;
        }

        .totals-table tr.total-row td.value {
            color: white;
            font-size: 14px;
        }

        .totals-table tr.discount-row td.value {
            color: #27AE60;
        }

        /* ============================================ */
        /* STATUS BADGE                                 */
        /* ============================================ */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-confirmed {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .status-processing {
            background-color: #FED7AA;
            color: #9A3412;
        }

        .status-shipped {
            background-color: #E9D5FF;
            color: #6B21A8;
        }

        .status-delivered {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-cancelled {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        /* ============================================ */
        /* FOOTER                                       */
        /* ============================================ */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #E5E5E5;
            text-align: center;
            font-size: 9px;
            color: #7F8C8D;
            line-height: 1.6;
        }

        .footer strong {
            color: #2C3E50;
        }

        .thank-you {
            margin-top: 25px;
            padding: 15px;
            background-color: #F5F6FA;
            border-radius: 6px;
            text-align: center;
            font-size: 12px;
            color: #2C3E50;
        }

        .thank-you strong {
            color: #E67E22;
            font-size: 14px;
        }

        /* ============================================ */
        /* WATERMARK (optionnel)                        */
        /* ============================================ */
        .watermark {
            position: fixed;
            top: 45%;
            left: 20%;
            font-size: 80px;
            color: #2C3E50;
            opacity: 0.03;
            transform: rotate(-30deg);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>

    {{-- Watermark discret --}}
    <div class="watermark">SHOPPRO</div>

    {{-- ============================================ --}}
    {{-- HEADER                                       --}}
    {{-- ============================================ --}}
    <div class="header">
        <div class="header-left">
            <div class="logo">Shop<span>Pro</span></div>
            <div class="company-info">
                <strong>ShopPro SARL</strong><br>
                Akwa, Douala - Cameroun<br>
                Tél : +237 6 50 00 00 00<br>
                Email : contact@shoppro.com
            </div>
        </div>

        <div class="header-right">
            <div class="invoice-title">Facture</div>
            <div class="invoice-number">
                N° {{ $invoice_number }}
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- INFO BLOCKS                                  --}}
    {{-- ============================================ --}}
    <div class="info-section">
        {{-- Client --}}
        <div class="info-block">
            <h3>Client</h3>
            <p><strong>{{ $user->name ?? 'N/A' }}</strong></p>
            <p>{{ $user->email ?? '' }}</p>
            @if($user && $user->profile)
                @if($user->profile->phone)
                    <p>Tél : {{ $user->profile->phone }}</p>
                @endif
                @if($user->profile->address)
                    <p>{{ $user->profile->address }}</p>
                @endif
                @if($user->profile->city)
                    <p>{{ $user->profile->city }}, {{ $user->profile->country ?? '' }}</p>
                @endif
            @endif
        </div>

        {{-- Livraison --}}
        @if($shippingAddress)
        <div class="info-block">
            <h3>Adresse de livraison</h3>
            <p><strong>{{ $shippingAddress->full_name }}</strong></p>
            <p>{{ $shippingAddress->phone }}</p>
            <p>{{ $shippingAddress->address_line1 }}</p>
            @if($shippingAddress->address_line2)
                <p>{{ $shippingAddress->address_line2 }}</p>
            @endif
            <p>{{ $shippingAddress->city }}, {{ $shippingAddress->country }}</p>
        </div>
        @endif

        {{-- Commande --}}
        <div class="info-block">
            <h3>Commande</h3>
            <p><strong>N° {{ $order->order_number }}</strong></p>
            <p>Date : {{ $order->created_at->format('d/m/Y') }}</p>
            <p>
                Statut :
                <span class="status-badge status-{{ $order->status }}">
                    {{ $order->status_label ?? $order->status }}
                </span>
            </p>
            <p>
                Paiement :
                <strong>{{ $order->payment_status_label ?? $order->payment_status }}</strong>
            </p>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- TABLE ARTICLES                               --}}
    {{-- ============================================ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Article</th>
                <th class="center" style="width: 10%;">Qté</th>
                <th class="right" style="width: 20%;">Prix unitaire</th>
                <th class="right" style="width: 20%;">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>
                        <div class="product-name">{{ $item->product_name }}</div>
                        @if($item->product_sku)
                            <div class="product-sku">SKU : {{ $item->product_sku }}</div>
                        @endif
                        @if($item->variant_name || $item->variant_color || $item->variant_size)
                            <div class="product-variant">
                                @if($item->variant_name) {{ $item->variant_name }} @endif
                                @if($item->variant_color) • {{ $item->variant_color }} @endif
                                @if($item->variant_size) • {{ $item->variant_size }} @endif
                            </div>
                        @endif
                    </td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">
                        {{ number_format($item->unit_price, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="right">
                        <strong>{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ============================================ --}}
    {{-- TOTAUX                                       --}}
    {{-- ============================================ --}}
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td class="label">Sous-total</td>
                <td class="value">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</td>
            </tr>

            @if($order->discount > 0)
            <tr class="discount-row">
                <td class="label">Remise</td>
                <td class="value">- {{ number_format($order->discount, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif

            @if($order->coupon_discount > 0)
            <tr class="discount-row">
                <td class="label">Code promo ({{ $order->coupon_code }})</td>
                <td class="value">- {{ number_format($order->coupon_discount, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif

            <tr>
                <td class="label">Livraison ({{ $order->shipping_method ?? 'Standard' }})</td>
                <td class="value">
                    @if($order->shipping_fee > 0)
                        {{ number_format($order->shipping_fee, 0, ',', ' ') }} FCFA
                    @else
                        Gratuite
                    @endif
                </td>
            </tr>

            @if($order->tax > 0)
            <tr>
                <td class="label">Taxes</td>
                <td class="value">{{ number_format($order->tax, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif

            <tr class="total-row">
                <td class="label"><strong>TOTAL</strong></td>
                <td class="value">
                    <strong>{{ number_format($order->total, 0, ',', ' ') }} {{ $order->currency }}</strong>
                </td>
            </tr>
        </table>
    </div>

    {{-- ============================================ --}}
    {{-- NOTE CLIENT                                  --}}
    {{-- ============================================ --}}
    @if($order->customer_note)
        <div class="thank-you">
            <strong>Note :</strong> {{ $order->customer_note }}
        </div>
    @endif

    {{-- ============================================ --}}
    {{-- REMERCIEMENT                                 --}}
    {{-- ============================================ --}}
    <div class="thank-you">
        <strong>Merci pour votre confiance !</strong><br>
        Pour toute question, contactez-nous à contact@shoppro.com
    </div>

    {{-- ============================================ --}}
    {{-- FOOTER                                       --}}
    {{-- ============================================ --}}
    <div class="footer">
        <strong>ShopPro SARL</strong> — Akwa, Douala, Cameroun<br>
        RC : XXXX-XXXX | NIU : XXXXXXXXXXXXXX | Compte bancaire : XXXXXXXXXXXXX<br>
        Facture générée le {{ $generated_at->format('d/m/Y à H:i') }}<br>
        <br>
        <em>Cette facture est générée automatiquement et fait foi de votre achat.</em>
    </div>

</body>
</html>