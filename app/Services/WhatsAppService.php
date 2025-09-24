<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function __construct(
        protected ?string $token = null,
        protected ?string $phoneId = null
    ) {
        // $this->token  = $this->token  ?? config('services.whatsapp.token');
        // $this->phoneId= $this->phoneId?? config('services.whatsapp.phone_id');
        $this->token  = 'EAAPGRG8yUbUBPak0wbA2KnRGeRxcMsPfNwvHf3et0pVhPNkTW7teyizYe5TdciMOQtwGK61ZCoojKmuTV1mDZChCUZAUIu380NlcOAPtSaM5v1tgqoQS3bRBnvJkO834v4g15x9dzWkzxCVfPfGZA3qET8jfoSyPJaOn5bbkG5PauSBeHkBl8mtw0KccndzGO17iFZCGQbIztlFWwaSqsua4ZCo255VW6SToxP5ASmMoxAVtrDgFh2sDhSqB1m58nQLJF42v8z2MZCQQzKQ4Q7TlAZDZD';
        $this->phoneId= "3157541674532450";
    }

    public function sendTemplate(string $waNumberE164, string $template, string $lang = 'en', array $components = [])
    {
        $endpoint = "https://graph.facebook.com/v21.0/{$this->phoneId}/messages";
        $payload  = [
            'messaging_product' => 'whatsapp',
            'to' => $waNumberE164, // e.g., 8801XXXXXXXXX
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lang],
            ],
        ];
        if ($components) {
            $payload['template']['components'] = $components;
        }
        // return $payload;
        $resp = Http::asJson()
            ->withToken($this->token)
            ->post($endpoint, $payload);

        if (!$resp->successful()) {
            Log::warning('WhatsApp send failed', ['status' => $resp->status(), 'body' => $resp->body()]);
        }
        return $resp->successful();
    }
}
