<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppBotController extends Controller
{
    public function handle(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');

        Log::info('🔔 Webhook recibido', [
            'from' => $from,
            'body' => $body
        ]);

        return response('OK', 200);
    }
}
