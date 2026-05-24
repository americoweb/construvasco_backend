@extends('emails.layout')
@section('title', 'Orçamento recusado')
@section('content')
    @php
        $request = $quote->projectRequest;
        $client = $request?->user;
        $clientName = $client?->name ?? $client?->identifier ?? 'Cliente';
    @endphp
    <h1>Orçamento recusado pelo cliente</h1>
    <p>O cliente <strong>{{ $clientName }}</strong> recusou o orçamento de arquitectura.</p>
    <p>Pedido: <strong>{{ $request?->reference_code ?? '—' }}</strong> — «{{ $request?->title ?? '—' }}»</p>
    <p>Valor proposto: <strong>{{ number_format((float) $quote->total_amount_mt, 2, ',', '.') }} MT</strong></p>
    @if ($quote->rejection_reason)
        <p>Motivo indicado pelo cliente:</p>
        <p><em>{{ $quote->rejection_reason }}</em></p>
    @endif
    <p>Pode enviar um novo orçamento na ficha do pedido na área de gestão.</p>
@endsection
