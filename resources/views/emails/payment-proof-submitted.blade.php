@extends('emails.layout')
@section('title', 'Comprovativo recebido')
@section('content')
    <h1>Comprovativo de pagamento</h1>
    <p>Foi submetido um comprovativo de pagamento para revisão.</p>
    <p>Referência: <strong>{{ $payment->reference }}</strong></p>
@endsection
