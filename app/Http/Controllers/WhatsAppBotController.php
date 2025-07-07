<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;
use App\Models\Evento;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Cache;

class WhatsAppBotController extends Controller
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
    }

    public function handle(Request $request)
    {
        Log::info("MENSAJE LLEGÓ: ", $request->all());
        $from = $request->input('From');
        $body = strtolower(trim($request->input('Body')));

        $session = Cache::get($from, []);

        // Paso 1: Inicio
        if (empty($session)) {
            if (in_array($body, ['hola', 'quiero informacion'])) {
                $session['stage'] = 'confirmar_ver_paquetes';
                Cache::put($from, $session, 600);
                return $this->sendMessage($from, "Hola! Bienvenido(a) al Jardín de Eventos Los Pinos. ¿Deseas ver nuestros paquetes disponibles?
1. Sí
2. No");
            } else {
                return $this->sendMessage($from, "Hola. Escribe 'Hola' o 'Quiero informacion' para comenzar.");
            }
        }

        // Paso 2: Mostrar paquetes
        if ($session['stage'] === 'confirmar_ver_paquetes') {
            if ($body === '1') {
                $paquetes = Paquete::where('disponible', true)->get();
                $mensaje = "Nuestros paquetes disponibles:
";
                foreach ($paquetes as $i => $paquete) {
                    $mensaje .= ($i + 1) . ". {$paquete->nombre}: {$paquete->capacidad} personas - \${$paquete->precio}
";
                }
                $mensaje .= "
Responde con el número del paquete que deseas.";
                $session['stage'] = 'seleccionar_paquete';
                $session['paquetes'] = $paquetes->pluck('id')->toArray();
                Cache::put($from, $session, 600);
                return $this->sendMessage($from, $mensaje);
            } else {
                Cache::forget($from);
                return $this->sendMessage($from, "Gracias por tu interés. Si deseas más información, escribe 'Hola'.");
            }
        }

        // Paso 3: Selección de paquete
        if ($session['stage'] === 'seleccionar_paquete') {
            $index = intval($body) - 1;
            if (isset($session['paquetes'][$index])) {
                $session['paquete_id'] = $session['paquetes'][$index];
                $session['stage'] = 'pedir_fecha';
                Cache::put($from, $session, 600);
                return $this->sendMessage($from, "Por favor, indícame la fecha del evento (ejemplo: 15-08-2025).");
            } else {
                return $this->sendMessage($from, "Opción inválida. Por favor, responde con el número de uno de los paquetes.");
            }
        }

        // Paso 4: Fecha del evento
        if ($session['stage'] === 'pedir_fecha') {
            $fecha = \DateTime::createFromFormat('d-m-Y', $body);
            if ($fecha && $fecha->format('d-m-Y') === $body) {
                $fecha_str = $fecha->format('Y-m-d');
                $existe = Evento::where('fecha', $fecha_str)->exists();
                if ($existe) {
                    return $this->sendMessage($from, "Lo sentimos, ya hay un evento reservado para esa fecha. Por favor, elige otra.");
                } else {
                    $session['fecha'] = $fecha_str;
                    $session['stage'] = 'numero_personas';
                    Cache::put($from, $session, 600);
                    return $this->sendMessage($from, "Perfecto. ¿Cuántas personas asistirán al evento?");
                }
            } else {
                return $this->sendMessage($from, "Fecha inválida. Usa el formato DD-MM-AAAA (ejemplo: 15-08-2025).");
            }
        }

        // Paso 5: Número de personas
        if ($session['stage'] === 'numero_personas') {
            if (is_numeric($body)) {
                $session['numero_personas'] = intval($body);
                $session['stage'] = 'datos_cliente';
                Cache::put($from, $session, 600);
                return $this->sendMessage($from, "Para finalizar, escribe tu nombre completo.");
            } else {
                return $this->sendMessage($from, "Por favor, escribe un número válido.");
            }
        }

        // Paso 6: Nombre cliente
        if ($session['stage'] === 'datos_cliente') {
            $session['nombre_cliente'] = $body;
            $session['telefono_cliente'] = $from;
            $session['estatus'] = 'pendiente';

            Evento::create([
                'fecha' => $session['fecha'],
                'paquete_id' => $session['paquete_id'],
                'numero_personas' => $session['numero_personas'],
                'nombre_cliente' => $session['nombre_cliente'],
                'telefono_cliente' => $session['telefono_cliente'],
                'estatus' => $session['estatus'],
            ]);

            Cache::forget($from);
            return $this->sendMessage($from, "¡Gracias {$session['nombre_cliente']}! Tu solicitud ha sido registrada. Pronto nos pondremos en contacto para confirmar tu evento.");
        }

        return response()->json();
    }

    protected function sendMessage($to, $message)
    {
        $this->twilio->messages->create($to, [
            'from' => env('TWILIO_WHATSAPP_FROM'),
            'body' => $message,
        ]);
    }

}
