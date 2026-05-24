@extends('emails.layout')
@section('title', 'Comprovativo rejeitado')
@section('content')
    <h1>Comprovativo rejeitado</h1>
    <p>O comprovativo de pagamento que submeteu foi rejeitado.</p>
    @if($payment->rejected_reason)
        <p><strong>Motivo:</strong> {{ $payment->rejected_reason }}</p>
    @endif
    <p>Pode submeter um novo comprovativo na ficha do projecto.</p>
@endsection
