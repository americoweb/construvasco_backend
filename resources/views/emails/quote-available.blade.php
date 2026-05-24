@extends('emails.layout')
@section('title', 'Orçamento disponível')
@section('content')
    @php
        $isConstruction = ($quote->quote_type?->value ?? $quote->quote_type) === 'construction';
        $phaseLabel = $isConstruction ? 'obra' : 'arquitectura';
    @endphp
    <h1>Orçamento de {{ $phaseLabel }} disponível</h1>
    <p>Recebeu um novo orçamento de <strong>{{ $phaseLabel }}</strong> para o pedido «{{ $quote->projectRequest->title ?? '—' }}».</p>
    <p>Valor: <strong>{{ number_format((float) $quote->total_amount_mt, 2, ',', '.') }} MT</strong></p>
    <p>Aceda à sua conta para rever e responder.</p>
@endsection
