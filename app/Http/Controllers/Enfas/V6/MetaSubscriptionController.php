<?php
namespace App\Http\Controllers\Enfas\V6;

use App\Http\Controllers\Controller;
use App\Services\Enfas\MetaWhatsAppService;

class MetaSubscriptionController extends Controller
{
    public function subscribe(MetaWhatsAppService $meta)
    {
        try {
            $meta->subscribeWaba();
            return back()->with('success','Aplicativo inscrito na WABA. Os eventos serão enviados ao webhook configurado no app da Meta.');
        } catch(\Throwable $e) {
            report($e);
            return back()->withErrors(['meta'=>$e->getMessage()]);
        }
    }
}
