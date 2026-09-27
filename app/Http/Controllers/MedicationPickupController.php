<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentEvent;
use Illuminate\Http\Request;

class MedicationPickupController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        $query = Appointment::query()
            ->with(['patient','professional','service'])
            ->where('appointment_type', 'medication_pickup');

        if ($status !== '') {
            $query->where('pickup_status', $status);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('medication_name', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patient) use ($search) {
                        $patient
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('preferred_name', 'like', "%{$search}%")
                            ->orWhere('rgea_number', 'like', "%{$search}%");
                    });
            });
        }

        $pickups = $query
            ->orderByRaw("CASE WHEN pickup_status IN ('scheduled','preparing','ready') THEN 0 ELSE 1 END")
            ->orderBy('start_at')
            ->paginate(40)
            ->withQueryString();

        $metricsBase = Appointment::query()
            ->where('appointment_type', 'medication_pickup');

        $metrics = [
            'scheduled' => (clone $metricsBase)->where('pickup_status', 'scheduled')->count(),
            'preparing' => (clone $metricsBase)->where('pickup_status', 'preparing')->count(),
            'ready' => (clone $metricsBase)->where('pickup_status', 'ready')->count(),
            'collected_today' => (clone $metricsBase)
                ->where('pickup_status', 'collected')
                ->whereDate('pickup_collected_at', today())
                ->count(),
        ];

        return view('medication-pickups.index', compact('pickups','metrics','status','search'));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        abort_unless(
            $appointment->appointment_type === 'medication_pickup',
            404
        );

        $data = $request->validate([
            'pickup_status' => [
                'required',
                'in:scheduled,preparing,ready,collected,not_collected,cancelled',
            ],
            'pickup_collected_by' => ['nullable','string','max:160'],
            'pickup_collector_document' => ['nullable','string','max:80'],
        ]);

        if (
            $data['pickup_status'] === 'collected'
            && blank($data['pickup_collected_by'] ?? null)
        ) {
            return back()->withErrors([
                'pickup_collected_by' => 'Informe quem realizou a retirada.',
            ]);
        }

        $old = $appointment->pickup_status ?: 'scheduled';

        $allowedTransitions = [
            'scheduled' => ['scheduled','preparing','cancelled'],
            'preparing' => ['preparing','ready','cancelled'],
            'ready' => ['ready','collected','not_collected','cancelled'],
            'collected' => ['collected'],
            'not_collected' => ['not_collected'],
            'cancelled' => ['cancelled'],
        ];

        abort_unless(
            in_array($data['pickup_status'], $allowedTransitions[$old] ?? [], true),
            422,
            'Transição de status inválida para esta retirada.'
        );

        $appointment->pickup_status = $data['pickup_status'];
        $appointment->updated_by = auth()->id();

        if ($data['pickup_status'] === 'ready') {
            $appointment->pickup_ready_at = now();
        }

        if ($data['pickup_status'] === 'collected') {
            $appointment->pickup_collected_at = now();
            $appointment->pickup_collected_by = trim($data['pickup_collected_by']);
            $appointment->pickup_collector_document = $data['pickup_collector_document'] ?? null;
            $appointment->status = 'completed';
            $appointment->completed_at = $appointment->completed_at ?: now();
        }

        if ($data['pickup_status'] === 'not_collected') {
            $appointment->status = 'no_show';
        }

        if ($data['pickup_status'] === 'cancelled') {
            $appointment->status = 'cancelled';
            $appointment->cancelled_at = $appointment->cancelled_at ?: now();
        }

        $appointment->save();

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'event_type' => 'medication_pickup_status',
            'title' => 'Retirada de medicamento atualizada',
            'description' => ($old ?: 'sem status').' → '.$appointment->pickup_status,
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Status da retirada atualizado.');
    }
}
