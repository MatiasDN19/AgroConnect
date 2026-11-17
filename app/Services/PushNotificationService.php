<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send push notification via Firebase Cloud Messaging.
     */
    public function sendPush(User $user, Alerta $alerta): bool
    {
        if (empty($user->fcm_token)) {
            return false;
        }

        $serverKey = config('services.fcm.server_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $user->fcm_token,
                'notification' => [
                    'title' => 'AgroConnect',
                    'body' => $alerta->mensaje,
                    'sound' => 'default',
                ],
                'data' => [
                    'alerta_id' => $alerta->id,
                    'tipo' => $alerta->tipo,
                ],
            ]);

            if ($response->failed()) {
                Log::warning('FCM push notification error', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM push notification exception: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e,
            ]);

            return false;
        }
    }
}
