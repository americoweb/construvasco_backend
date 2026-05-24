@extends('emails.layout')
@section('title', 'Obra concluída')
@section('content')
    <h1>Obra concluída</h1>
    <p>A obra do seu projecto <strong>{{ $project->name }}</strong> foi marcada como concluída pela equipa Construvasco.</p>
    <p><strong>Fase:</strong> Concluído</p>
    @if($project->construction_completed_at)
        <p><strong>Data:</strong> {{ $project->construction_completed_at->format('d/m/Y') }}</p>
    @endif
    <p>Obrigado por ter confiado na Construvasco. Para qualquer questão, contacte-nos directamente.</p>
@endsection
