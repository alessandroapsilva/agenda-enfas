<?php
namespace App\Jobs;

use App\Models\WaTemplate;
use App\Services\Enfas\MetaWhatsAppService;
use App\Services\Enfas\WhatsAppDispatchPolicy;
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

    public function handle(
        MetaWhatsAppService $meta,
        WhatsAppDispatchPolicy $policy
    ): void {
        $template = WaTemplate::findOrFail(
            $this->templateId
        );

        if (! $policy->patientAllowsContact(
            $this->appointmentId
        )) {
            return;
        }

        if ($this->automationId
            && ! $policy->automatedMessageAllowed(
                $this->appointmentId,
                (string) (
                    $template->purpose
                    ?: 'general'
                )
            )) {
            return;
        }

        $meta->sendAppointmentTemplate(
            $this->appointmentId,
            $template,
            $this->automationId,
            $this->dedupeKey
        );
    }
}
