@extends('emails.layout')
@section('title', 'Orçamento disponível')
@section('content')
    <h1>Orçamento disponível</h1>
    <p>Recebeu um novo orçamento para o pedido «{{ $quote->projectRequest->title ?? '—' }}».</p>
    <p>Valor: <strong>{{ number_format((float) $quote->total_amount_mt, 2, ',', '.') }} MT</strong></p>
    <p>Aceda à sua conta para rever e responder.</p>
@endsection
