@extends('emails.layout')
@section('title', 'Entregável disponível')
@section('content')
    <h1>Entregável disponível</h1>
    <p>Um novo entregável está disponível: <strong>{{ $deliverable->title }}</strong>.</p>
    <p>Aceda à sua conta para consultar.</p>
@endsection
