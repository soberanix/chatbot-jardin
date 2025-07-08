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
        $body = strtolower(trim($request->input('Body'))); // Convertir a minúsculas y sin espacios

        Log::channel('whatsapp')->info('🔔 Webhook recibido', [
            'from' => $from,
            'body' => $body,
        ]);

        // Si el mensaje es "hola", enviar bienvenida
        if ($body === 'hola') {
            $this->responderWhatsApp($from, "👋 ¡Hola! Bienvenido al Jardín de Eventos IBP 🌺\n\nPuedes escribir una de las siguientes palabras para continuar:\n- *paquetes*\n- *eventos disponibles*\n- *reservar*");
        }

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
