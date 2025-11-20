<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected $wap;
    public function __construct(protected WhatsAppService $wa) {
        $this->wap = $wa;
    }

    public function sendMessage()
    {

        // $msg = $notification->toWhatsApp($notifiable);
        $msg = ['to'=>'+8801795331001','template'=>'doc_expiring_tomorrow','lang'=>'en','components'=>[]];

        if (empty($msg['to']) || empty($msg['template'])) return;

        return $this->wap->sendTemplate(
            $msg['to'],
            $msg['template'],
            $msg['lang'] ?? 'en',
            $msg['components'] ?? []
        );
    }
}
