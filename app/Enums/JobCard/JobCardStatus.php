<?php

namespace App\Enums\JobCard;

enum JobCardStatus: string
{
    case DRAFT      = 'draft';
    case BRIEFING   = 'briefing';
    case DESIGN     = 'design';
    case REVISION   = 'revision';
    case APPROVAL   = 'approval';
    case PRODUCTION = 'production';
    case DONE       = 'done';
    case CANCELLED  = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT      => 'Rascunho',
            self::BRIEFING   => 'Briefing',
            self::DESIGN     => 'Design',
            self::REVISION   => 'Revisão',
            self::APPROVAL   => 'Aprovação',
            self::PRODUCTION => 'Produção',
            self::DONE       => 'Concluído',
            self::CANCELLED  => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT      => 'gray',
            self::BRIEFING   => 'blue',
            self::DESIGN     => 'purple',
            self::REVISION   => 'yellow',
            self::APPROVAL   => 'orange',
            self::PRODUCTION => 'indigo',
            self::DONE       => 'green',
            self::CANCELLED  => 'red',
        };
    }

    public function canTransitionTo(JobCardStatus $newStatus): bool
    {
        return match($this) {
            self::DRAFT      => in_array($newStatus, [self::BRIEFING, self::CANCELLED]),
            self::BRIEFING   => in_array($newStatus, [self::DESIGN, self::DRAFT, self::CANCELLED]),
            self::DESIGN     => in_array($newStatus, [self::REVISION, self::APPROVAL, self::CANCELLED]),
            self::REVISION   => in_array($newStatus, [self::DESIGN, self::APPROVAL, self::CANCELLED]),
            self::APPROVAL   => in_array($newStatus, [self::PRODUCTION, self::REVISION, self::CANCELLED]),
            self::PRODUCTION => in_array($newStatus, [self::DONE, self::CANCELLED]),
            self::DONE       => false,
            self::CANCELLED  => false,
        };
    }

    /** Statuses that count as "active" (not terminal) */
    public function isActive(): bool
    {
        return !in_array($this, [self::DONE, self::CANCELLED]);
    }

    /** Kanban columns (excludes draft and cancelled as sidebar items) */
    public static function kanbanColumns(): array
    {
        return [
            self::BRIEFING,
            self::DESIGN,
            self::REVISION,
            self::APPROVAL,
            self::PRODUCTION,
        ];
    }
}
