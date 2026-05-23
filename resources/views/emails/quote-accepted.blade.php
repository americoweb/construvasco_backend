@extends('emails.layout')
@section('title', 'Orçamento aceite')
@section('content')
    <h1>Orçamento aceite</h1>
    <p>O cliente aceitou o orçamento do pedido «{{ $quote->projectRequest->title ?? '—' }}».</p>
    <p>Projecto: <strong>{{ $project->name }}</strong></p>
@endsection
