<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30; // seconds

    public function __construct(
        public readonly string $phone,
        public readonly string $message
    ) {
        $this->onQueue('notifications');
    }

    public function handle(WhatsAppService $whatsApp): void
    {
        $whatsApp->send($this->phone, $this->message);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendWhatsAppNotification failed permanently', [
            'phone'   => $this->phone,
            'message' => $this->message,
            'error'   => $e->getMessage(),
        ]);
    }
}
