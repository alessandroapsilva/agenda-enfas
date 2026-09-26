<?php
namespace App\Jobs;

use App\Models\WaTemplate;
use App\Services\Enfas\MetaWhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAppointmentWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 90;

    public function __construct(
        public int $appointmentId,
        public int $templateId,
        public ?int $automationId = null,
        public ?string $dedupeKey = null
    ) {}

    public function handle(MetaWhatsAppService $meta): void
    {
        $meta->sendAppointmentTemplate(
            $this->appointmentId,
            WaTemplate::findOrFail($this->templateId),
            $this->automationId,
            $this->dedupeKey
        );
    }
}
