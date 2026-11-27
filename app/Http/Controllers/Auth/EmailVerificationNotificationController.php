<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'El correo electrónico ya está verificado.']);
            }

            return redirect()->intended(route('publicaciones.index', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Se envió un nuevo enlace de verificación a tu correo.']);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
