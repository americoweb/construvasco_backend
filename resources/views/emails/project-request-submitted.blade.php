@extends('emails.layout')
@section('title', 'Pedido submetido')
@section('content')
    <h1>Pedido submetido</h1>
    <p>Olá,</p>
    <p>O seu pedido <strong>{{ $projectRequest->reference_code }}</strong> — «{{ $projectRequest->title }}» — foi recebido pela equipa Construvasco.</p>
    <p>Entraremos em contacto após a análise do briefing.</p>
@endsection
