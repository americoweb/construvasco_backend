<?php

namespace App\Support;

use App\Enums\ProjectContractPhase;

class ProjectContractPhaseLabel
{
    public static function for(ProjectContractPhase|string|null $phase): string
    {
        $value = $phase instanceof ProjectContractPhase ? $phase->value : (string) $phase;

        return match ($value) {
            ProjectContractPhase::Architecture->value => 'Arquitectura',
            ProjectContractPhase::ExecutionQuote->value => 'Aguarda orçamento de obra',
            ProjectContractPhase::Construction->value => 'Obra em andamento',
            ProjectContractPhase::Completed->value => 'Concluído',
            ProjectContractPhase::Closed->value => 'Encerrado',
            default => $value !== '' ? $value : '—',
        };
    }
}
