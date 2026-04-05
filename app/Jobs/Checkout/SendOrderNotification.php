<?php

namespace App\Jobs\Checkout;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order\Order;
use Illuminate\Support\Facades\Log;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(): void
    {
        // Send WhatsApp notification (integrate with WhatsApp Business API)
        $whatsapp = $this->order->shipping_whatsapp;
        $message = $this->buildMessage();
        
        // TODO: Integrate with actual WhatsApp Business API
        Log::info('WhatsApp notification would be sent', [
            'order_id' => $this->order->id,
            'whatsapp' => $whatsapp,
            'message' => $message,
        ]);
    }

    protected function buildMessage(): string
    {
        return "Olá {$this->order->shipping_name}! 🎉\n\n"
            . "Seu pedido #{$this->order->order_number} foi recebido com sucesso!\n\n"
            . "Total: {$this->order->formatted_total}\n\n"
            . "Entraremos em contacto em breve para confirmar os detalhes.\n\n"
            . "Obrigado por escolher Amazing Brindes!";
    }
}
