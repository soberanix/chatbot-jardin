<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use App\Models\Paquete;
use App\Models\Evento;
use Carbon\Carbon;

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
        
        switch ($body) {
        case 'hola':
            $this->responderWhatsApp($from, "👋 ¡Hola! Bienvenido al Jardín de Eventos IBP 🌸\nEscribe:\n- *paquetes*\n- *eventos*\n- *reservar*");
            break;

        case 'paquetes':
            $paquetes = Paquete::all();
            if ($paquetes->isEmpty()) {
                $this->responderWhatsApp($from, "📦 Aún no hay paquetes registrados.");
            } else {
                $mensaje = "🎁 *Paquetes disponibles:*\n";
                foreach ($paquetes as $paquete) {
                    $mensaje .= "\n• *{$paquete->nombre}* ";
                }
                $this->responderWhatsApp($from, $mensaje);
            }
            break;

        case 'eventos':
            $eventos = Evento::whereDate('fecha', '>=', now())->orderBy('fecha')->get();
            if ($eventos->isEmpty()) {
                $this->responderWhatsApp($from, "📅 No hay eventos programados actualmente.");
            } else {
                $mensaje = "🎉 *Próximos eventos:*\n";
                foreach ($eventos as $evento) {
                    $mensaje .= "\n• *{$evento->nombre}* el *" . \Carbon\Carbon::parse($evento->fecha)->format('d/m/Y') . "*";
                }
                $this->responderWhatsApp($from, $mensaje);
            }
            break;

        default:
            $this->responderWhatsApp($from, "🤖 No entendí tu mensaje. Escribe *hola*, *paquetes* o *eventos*.");
            break;
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
