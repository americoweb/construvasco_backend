@extends('emails.layout')
@section('title', 'Comprovativo rejeitado')
@section('content')
    @php
        $isConstruction = ($payment->phase?->value ?? $payment->phase) === 'construction';
        $phaseLabel = $isConstruction ? 'obra' : 'arquitectura';
    @endphp
    <h1>Comprovativo de {{ $phaseLabel }} rejeitado</h1>
    <p>O comprovativo de pagamento de <strong>{{ $phaseLabel }}</strong> que submeteu foi rejeitado.</p>
    @if($payment->rejected_reason)
        <p><strong>Motivo:</strong> {{ $payment->rejected_reason }}</p>
    @endif
    <p>Pode submeter um novo comprovativo na ficha do projecto.</p>
@endsection
