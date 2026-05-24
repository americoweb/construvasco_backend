@extends('emails.layout')
@section('title', 'Arquitectura concluída')
@section('content')
    <h1>Arquitectura concluída</h1>
    <p>A arquitectura do seu projecto «{{ $project->name }}» está concluída.</p>
    <p>Já pode solicitar o orçamento de obra na sua área de cliente.</p>
@endsection
