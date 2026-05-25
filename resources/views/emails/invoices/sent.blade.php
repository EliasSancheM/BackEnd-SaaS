<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h1>{{ $invoice->tenant->name }}</h1>

    <p>Hola <strong>{{ $invoice->client->name }}</strong>,</p>

    <p>Adjuntamos la factura <strong>{{ $invoice->number }}</strong> por un total de
    <strong>${{ number_format($invoice->total, 2) }}</strong>.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <th style="text-align: left; border-bottom: 1px solid #ddd; padding: 8px;">Descripcion</th>
            <th style="text-align: right; border-bottom: 1px solid #ddd; padding: 8px;">Cant.</th>
            <th style="text-align: right; border-bottom: 1px solid #ddd; padding: 8px;">Precio</th>
            <th style="text-align: right; border-bottom: 1px solid #ddd; padding: 8px;">Total</th>
        </tr>
        @foreach ($invoice->items as $item)
            <tr>
                <td style="padding: 8px;">{{ $item->description }}</td>
                <td style="text-align: right; padding: 8px;">{{ $item->quantity }}</td>
                <td style="text-align: right; padding: 8px;">${{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right; padding: 8px;">${{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <p>Vencimiento: {{ $invoice->due_date?->format('d/m/Y') }}</p>

    <p>Gracias por tu preferencia.</p>
</body>
</html>
