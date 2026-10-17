@php
    use App\Support\Money;
    $width = (int) setting('receipt.paper_width', 58);
    $cols = $width >= 80 ? 48 : 32;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $sale->number }}</title>
    <style>
        @page { size: {{ $width }}mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: {{ $width }}mm;
            margin: 0 auto;
            padding: 2mm;
            color: #000;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 0; vertical-align: top; }
        .logo { max-width: 100%; max-height: 60px; margin-bottom: 4px; }
        .copy-badge { text-align: center; font-weight: bold; margin-bottom: 4px; }
    </style>
</head>
<body>
    @if($copy ?? false)
        <p class="copy-badge">*** SALINAN ***</p>
    @endif

    <div class="center">
        @if(setting('receipt.show_logo') && setting('store.logo_path'))
            <img class="logo" src="{{ asset('storage/'.setting('store.logo_path')) }}" alt="Logo">
        @endif
        <p class="bold">{{ setting('store.name', config('app.name')) }}</p>
        @if(setting('store.address'))<p>{{ setting('store.address') }}</p>@endif
        @if(setting('store.phone'))<p>{{ setting('store.phone') }}</p>@endif
    </div>

    <div class="line"></div>

    <p>
        No: {{ $sale->number }}<br>
        {{ $sale->sold_at->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') }} WIB<br>
        Kasir: {{ $sale->cashier->name }}<br>
        Pelanggan: {{ $sale->customer->name ?? 'Umum' }}
    </p>

    <div class="line"></div>

    @foreach($sale->items as $item)
        <p>{{ $item->product_name }}</p>
        <table>
            <tr>
                <td>{{ rtrim(rtrim(number_format($item->qty, 3), '0'), '.') }} {{ $item->unit_name }} x {{ Money::format($item->price, false) }}</td>
                <td class="right">{{ Money::format($item->subtotal, false) }}</td>
            </tr>
        </table>
        @if($item->discount_amount > 0)
            <table><tr><td>Diskon</td><td class="right">-{{ Money::format($item->discount_amount, false) }}</td></tr></table>
        @endif
    @endforeach

    <div class="line"></div>

    <table>
        <tr><td>Subtotal</td><td class="right">{{ Money::format($sale->subtotal, false) }}</td></tr>
        @if($sale->discount_amount > 0)
            <tr><td>Diskon</td><td class="right">-{{ Money::format($sale->discount_amount, false) }}</td></tr>
        @endif
        @if($sale->tax_amount > 0)
            <tr><td>Pajak</td><td class="right">{{ Money::format($sale->tax_amount, false) }}</td></tr>
        @endif
        @if($sale->rounding_amount != 0)
            <tr><td>Pembulatan</td><td class="right">{{ Money::format($sale->rounding_amount, false) }}</td></tr>
        @endif
        <tr class="bold"><td>TOTAL</td><td class="right">{{ Money::format($sale->total, false) }}</td></tr>
    </table>

    <div class="line"></div>

    @foreach($sale->payments as $payment)
        <table><tr><td>{{ ucfirst($payment->method->value) }}</td><td class="right">{{ Money::format($payment->amount, false) }}</td></tr></table>
    @endforeach
    <table><tr><td>Kembalian</td><td class="right">{{ Money::format($sale->change_amount, false) }}</td></tr></table>

    <div class="line"></div>

    <div class="center">
        @foreach(explode("\n", setting('receipt.footer', '')) as $line)
            <p>{{ $line }}</p>
        @endforeach
    </div>

    <script>window.onload = () => {};</script>
</body>
</html>
