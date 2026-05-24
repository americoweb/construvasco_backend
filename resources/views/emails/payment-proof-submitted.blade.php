@extends('emails.layout')
@section('title', 'Comprovativo recebido')
@section('content')
    <h1>Comprovativo de pagamento</h1>
    <p>Foi submetido um comprovativo de pagamento para revisão.</p>
    <p>Referência: <strong>{{ $payment->reference }}</strong></p>
    @if($payment->project)
        <p>Projecto: <strong>{{ $payment->project->name }}</strong></p>
        <p>Valor: <strong>{{ number_format((float) $payment->amount, 2, ',', '.') }} MT</strong></p>
    @endif
    @if($payment->user)
        <p>Cliente: {{ $payment->user->name }}</p>
    @endif
@endsection
