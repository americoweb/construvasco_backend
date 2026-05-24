@extends('emails.layout')
@section('title', 'Pedido de orçamento de obra')
@section('content')
    <h1>Pedido de orçamento de obra</h1>
    <p><strong>{{ $project->client?->name ?? 'Cliente' }}</strong> solicitou orçamento de obra.</p>
    <p><strong>Projecto:</strong> {{ $project->name }}</p>
    <p><strong>Fase:</strong> Orçamento de obra (visita ao terreno)</p>
    @if($project->suggested_site_visit_date)
        <p><strong>Data sugerida para visita:</strong> {{ $project->suggested_site_visit_date->format('d/m/Y') }}</p>
    @endif
    @if($project->construction_request_notes)
        <p><strong>Notas do cliente:</strong><br>{{ $project->construction_request_notes }}</p>
    @endif
    <p>Próximo passo: agendar visita e enviar orçamento de obra no painel de gestão.</p>
@endsection
