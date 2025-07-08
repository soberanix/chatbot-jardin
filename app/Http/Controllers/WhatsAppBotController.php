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
        $body = strtolower(trim($request->input('Body')));
        $session = Cache::get($from, ['stage' => 'inicio']);
        
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
        switch ($session['stage']) {
    case 'inicio':
        if (in_array($body, ['hola', 'quiero informacion'])) {
            $this->responderWhatsApp($from, "Hola! Bienvenido(a) al Jardín de Eventos Los Pinos.\n¿Deseas ver nuestros paquetes disponibles?\n1. Sí\n2. No");
            $session['stage'] = 'esperando_confirmacion_paquetes';
            Cache::put($from, $session, now()->addMinutes(30));
        }
        break;

    case 'esperando_confirmacion_paquetes':
        if ($body == '1') {
            $paquetes = Paquete::all();
            if ($paquetes->isEmpty()) {
                $this->responderWhatsApp($from, "No hay paquetes disponibles en este momento.");
                break;
            }

            $mensaje = "🎁 *Paquetes disponibles:*\n";
            foreach ($paquetes as $paquete) {
                $mensaje .= "\n• *{$paquete->nombre}* - {$paquete->descripcion}";
            }

            $this->responderWhatsApp($from, $mensaje);
            $this->responderWhatsApp($from, "¿Cuál paquete te interesa?");
            $session['stage'] = 'esperando_nombre_paquete';
            Cache::put($from, $session, now()->addMinutes(30));
        } elseif ($body == '2') {
            $this->responderWhatsApp($from, "Gracias por tu interés. ¡Estamos a tus órdenes!");
            Cache::forget($from);
        }
        break;

    case 'esperando_nombre_paquete':
        $session['paquete'] = $body;
        $this->responderWhatsApp($from, "Ingresa la fecha del evento (ejemplo: 15-08-2025):");
        $session['stage'] = 'esperando_fecha';
        Cache::put($from, $session, now()->addMinutes(30));
        break;

    case 'esperando_fecha':
        $session['fecha'] = $body;
        $this->responderWhatsApp($from, "¿Cuántos invitados asistirán?");
        $session['stage'] = 'esperando_invitados';
        Cache::put($from, $session, now()->addMinutes(30));
        break;

    case 'esperando_invitados':
        $session['invitados'] = $body;

        // 🔄 Aquí iría la verificación con Google Calendar (te la agrego en el siguiente paso)
        $this->responderWhatsApp($from, "Verificando disponibilidad en la fecha {$session['fecha']}...");

        // Simulación: la fecha está libre
        $this->responderWhatsApp($from, "La fecha está disponible. Por favor proporciona:\n1. Tu nombre completo\n2. Número de contacto\n3. Correo electrónico");

        $session['stage'] = 'esperando_datos_cliente';
        Cache::put($from, $session, now()->addMinutes(30));
        break;

    case 'esperando_datos_cliente':
        $session['datos_cliente'] = $body;

        // 🔧 Aquí se crea el evento en Google Calendar (te mostraré el código)
        $this->responderWhatsApp($from, "🎉 ¡Gracias! Tu evento ha sido reservado con éxito en la fecha {$session['fecha']}.\nNos pondremos en contacto contigo.");

        Cache::forget($from);
        break;

    default:
        $this->responderWhatsApp($from, "🤖 No entendí tu mensaje. Escribe *hola* para comenzar.");
        Cache::forget($from);
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
