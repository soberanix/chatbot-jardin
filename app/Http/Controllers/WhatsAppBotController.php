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
        Log::channel('whatsapp')->info('TWILIO VARS', [
    'sid' => env('TWILIO_ACCOUNT_SID'),
    'token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_WHATSAPP_NUMBER'),
]);

        // Si el mensaje es "hola", enviar bienvenida
        if ($body === 'hola') {
            $this->responderWhatsApp($from, "👋 ¡Hola! Bienvenido al Jardín de Eventos IBP 🌺\n\nPuedes escribir una de las siguientes palabras para continuar:\n- *paquetes*\n- *eventos disponibles*\n- *reservar*");
        }

        return response('OK', 200);
    }

    private function responderWhatsApp($to, $mensaje)
{
    try {
        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);

        $from = env('TWILIO_WHATSAPP_NUMBER');

        Log::channel('whatsapp')->info('📤 Enviando mensaje', [
            'to' => $to,
            'from' => $from,
            'mensaje' => $mensaje
        ]);

        $twilio->messages->create($to, [
            'from' => $from,
            'body' => $mensaje
        ]);

    } catch (\Exception $e) {
        Log::channel('whatsapp')->error('❌ Error al enviar mensaje', [
            'exception' => $e->getMessage()
        ]);
    }
}

}
