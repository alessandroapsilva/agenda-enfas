<?php
namespace App\Http\Controllers\Enfas\V6;

use App\Http\Controllers\Controller;
use App\Jobs\SendAppointmentWhatsApp;
use App\Models\WaMessage;
use App\Models\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsAppMessageController extends Controller
{
    public function index()
    {
        return view('enfas.v6.whatsapp.messages',[
            'rows'=>WaMessage::with('template')->orderByDesc('id')->paginate(80),
            'templates'=>WaTemplate::where('status','APPROVED')->orderBy('name')->get(),
            'appointments'=>DB::table('appointments')
                ->join('patients','patients.id','=','appointments.patient_id')
                ->orderByDesc('appointments.start_at')->limit(100)
                ->get(['appointments.id','appointments.code','appointments.start_at','patients.name as patient_name']),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'appointment_id'=>'required|exists:appointments,id',
            'template_id'=>'required|exists:wa_templates,id',
        ]);

        SendAppointmentWhatsApp::dispatch(
            (int)$data['appointment_id'],
            (int)$data['template_id'],
            null,
            'manual:'.\Illuminate\Support\Str::uuid()
        );

        return back()->with('success','Mensagem adicionada à fila de envio.');
    }
}
