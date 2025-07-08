<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppBotController extends Controller
{
    public function handle(Request $request)
    {
        $from = $request->input('From');
        $body = strtolower(trim($request->input('Body')));

        Log::info('🔔 Webhook recibido', [
            'from' => $from,
            'body' => $body,
        ]);

        // Respuesta automática
        $this->responderWhatsApp($from, "Hola 👋 gracias por escribir. ¿En qué puedo ayudarte?");

        return response('OK', 200);
    }

    private function responderWhatsApp($to, $mensaje)
    {
        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);

        $twilio->messages->create($to, [
            'from' => 'whatsapp:' . env('TWILIO_WHATSAPP_NUMBER'),
            'body' => $mensaje
        ]);
    }
}
