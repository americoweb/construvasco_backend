<?php

namespace App\Services\AI;

use App\Models\Construction\ProjectRequest;

class BriefingPromptBuilder
{
    public function build(ProjectRequest $request): string
    {
        $parts = [];

        $parts[] = 'Render arquitectónico para projecto residencial/comercial em Moçambique.';
        $parts[] = 'Contexto: clima tropical, materiais locais, ventilação natural e integração com o terreno.';

        if ($request->project_type) {
            $parts[] = "Tipo de projecto: {$request->project_type}.";
        }
        if ($request->tipologia) {
            $parts[] = "Tipologia: {$request->tipologia}.";
        }
        if ($request->area_m2) {
            $parts[] = 'Área aproximada: ' . number_format((float) $request->area_m2, 0, ',', '.') . ' m².';
        }
        if ($request->num_pisos) {
            $parts[] = "Número de pisos: {$request->num_pisos}.";
        }
        if ($request->localizacao) {
            $parts[] = "Localização: {$request->localizacao}.";
        }

        $briefing = is_array($request->briefing_data) ? $request->briefing_data : [];

        $style = $briefing['estilo_arquitectonico'] ?? $request->estilo_arquitectonico;
        if ($style) {
            $parts[] = "Estilo arquitectónico: {$style}.";
        }

        $palette = $briefing['paleta_acabamento'] ?? $request->paleta_acabamento;
        if ($palette) {
            $parts[] = "Paleta e acabamentos: {$palette}.";
        }

        $program = $briefing['programa'] ?? $briefing['programa_espacos'] ?? null;
        if ($program) {
            $parts[] = 'Programa de espaços: ' . (is_string($program) ? $program : json_encode($program, JSON_UNESCAPED_UNICODE));
        }

        $terrain = $briefing['terreno'] ?? $briefing['condicoes_terreno'] ?? null;
        if ($terrain) {
            $parts[] = 'Terreno: ' . (is_string($terrain) ? $terrain : json_encode($terrain, JSON_UNESCAPED_UNICODE));
        }

        if ($request->description) {
            $parts[] = "Notas do cliente: {$request->description}.";
        }

        $parts[] = 'Mostrar fachada principal com boa iluminação diurna, escala humana e materiais realistas.';

        return implode("\n", $parts);
    }
}
