<?php

namespace App\Enums\Order;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case TRIAGED = 'triaged';
    case ASSIGNED = 'assigned';
    case IN_DESIGN = 'in_design';
    case AWAITING_CLIENT = 'awaiting_client';
    case APPROVED = 'approved';
    case IN_EXECUTION = 'in_execution';
    case CONFIRMED = 'confirmed';
    case IN_PRODUCTION = 'in_production';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::TRIAGED => 'Triado',
            self::ASSIGNED => 'Atribuído',
            self::IN_DESIGN => 'Em Projeto',
            self::AWAITING_CLIENT => 'Aguardando Cliente',
            self::APPROVED => 'Aprovado',
            self::IN_EXECUTION => 'Em Execução',
            self::CONFIRMED => 'Confirmado',
            self::IN_PRODUCTION => 'Em Produção',
            self::SHIPPED => 'Enviado',
            self::DELIVERED => 'Entregue',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::TRIAGED => 'blue',
            self::ASSIGNED => 'indigo',
            self::IN_DESIGN => 'purple',
            self::AWAITING_CLIENT => 'orange',
            self::APPROVED => 'teal',
            self::IN_EXECUTION => 'cyan',
            self::CONFIRMED => 'blue',
            self::IN_PRODUCTION => 'orange',
            self::SHIPPED => 'purple',
            self::DELIVERED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return match($this) {
            // Start of a construction workflow path
            self::PENDING => in_array($newStatus, [self::TRIAGED, self::CONFIRMED, self::CANCELLED]),
            self::TRIAGED => in_array($newStatus, [self::ASSIGNED, self::CANCELLED]),
            self::ASSIGNED => in_array($newStatus, [self::IN_DESIGN, self::CANCELLED]),
            self::IN_DESIGN => in_array($newStatus, [self::AWAITING_CLIENT, self::CANCELLED]),
            self::AWAITING_CLIENT => in_array($newStatus, [self::IN_DESIGN, self::APPROVED, self::CANCELLED]),
            self::APPROVED => in_array($newStatus, [self::IN_EXECUTION, self::CANCELLED]),
            self::IN_EXECUTION => in_array($newStatus, [self::DELIVERED, self::CANCELLED]),
            // Legacy print path kept for backward compatibility
            self::CONFIRMED => in_array($newStatus, [self::IN_PRODUCTION, self::CANCELLED]),
            self::IN_PRODUCTION => in_array($newStatus, [self::SHIPPED, self::CANCELLED]),
            self::SHIPPED => in_array($newStatus, [self::DELIVERED, self::CANCELLED]),
            self::DELIVERED => false,
            self::CANCELLED => false,
        };
    }
}
