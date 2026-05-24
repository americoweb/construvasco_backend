@extends('emails.layout')
@section('title', 'Pagamento confirmado')
@section('content')
    @php
        $isConstruction = ($payment->phase?->value ?? $payment->phase) === 'construction';
        $phaseLabel = $isConstruction ? 'obra' : 'arquitectura';
    @endphp
    <h1>Pagamento de {{ $phaseLabel }} confirmado</h1>
    <p>O seu pagamento de <strong>{{ $phaseLabel }}</strong> no valor de <strong>{{ number_format((float) $payment->amount, 2, ',', '.') }} MT</strong> foi confirmado.</p>
    @if($isConstruction)
        <p>A obra do seu projecto pode avançar. O acompanhamento detalhado é feito directamente com a equipa Construvasco.</p>
    @else
        <p>Já pode descarregar os entregáveis aprovados do seu projecto na área de cliente.</p>
    @endif
    @if($payment->project)
        <p>Projecto: <strong>{{ $payment->project->name }}</strong></p>
    @endif
@endsection
