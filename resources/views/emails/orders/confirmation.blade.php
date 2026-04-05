@component('mail::message')
# Confirmação do Pedido

Obrigado pelo seu pedido!

**Número do Pedido:** {{ $order->order_number }}
**Data:** {{ $order->created_at->format('d/m/Y H:i') }}

## Itens do Pedido

@component('mail::table')
| Produto | Cor | Quantidade | Preço |
|:--------|:----|:-----------|------:|
@foreach($items as $item)
| {{ $item->product_name }} | {{ $item->color_name }} | {{ $item->quantity }} | {{ number_format($item->total_price, 2, ',', '.') }} MT |
@endforeach
@endcomponent

**Subtotal:** {{ number_format($order->subtotal, 2, ',', '.') }} MT
@if($order->shipping_cost > 0)
**Frete:** {{ number_format($order->shipping_cost, 2, ',', '.') }} MT
@endif
@if($order->discount_amount > 0)
**Desconto:** -{{ number_format($order->discount_amount, 2, ',', '.') }} MT
@endif
**Total:** {{ $formattedTotal }}

## Endereço de Entrega

{{ $order->shipping_name }}
{{ $order->shipping_address }}
{{ $order->shipping_city }}, {{ $order->shipping_state }}
{{ $order->shipping_country }}
WhatsApp: {{ $order->shipping_whatsapp }}

@component('mail::button', ['url' => config('app.url') . '/orders/' . $order->uuid])
Ver Pedido
@endcomponent

Entraremos em contacto pelo WhatsApp para confirmar os detalhes.

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
