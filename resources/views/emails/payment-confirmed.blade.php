@extends('emails.layout')
@section('title', 'Pagamento confirmado')
@section('content')
    <h1>Pagamento confirmado</h1>
    <p>O seu pagamento de <strong>{{ number_format((float) $payment->amount, 2, ',', '.') }} MT</strong> foi confirmado.</p>
    <p>Já pode descarregar os entregáveis aprovados do seu projecto na área de cliente.</p>
    @if($payment->project)
        <p>Projecto: <strong>{{ $payment->project->name }}</strong></p>
    @endif
@endsection
