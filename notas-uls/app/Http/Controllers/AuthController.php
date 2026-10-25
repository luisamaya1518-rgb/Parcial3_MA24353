<?php

namespace App\Http\Controllers;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 422);
        }
        if ($user->estado !== 'habilitado') {
            return response()->json(['message' => 'Usuario inhabilitado'], 403);
        }

        // Siempre token temporal: nadie entra solo con la contraseña
        $token = $user->createToken('pre-2fa', ['pre-2fa'], now()->addMinutes(10))->plainTextToken;
        $response = [
            'token' => $token,
            'requires_qr' => !$user->two_factor_confirmed_at,
        ];

        // El QR se muestra únicamente mientras el 2FA no esté confirmado
        if (!$user->two_factor_confirmed_at) {
            $google2fa = new Google2FA();
            if (!$user->two_factor_secret) {
                $user->two_factor_secret = $google2fa->generateSecretKey();
                $user->save();
            }
            $url = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $user->two_factor_secret);
            $svg = (new Writer(new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd())))->writeString($url);
            $response['qr'] = 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        return response()->json($response);
    }

    public function verify2fa(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $user = $request->user();
        $valid = (new Google2FA())->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return response()->json(['message' => 'Código inválido'], 422);
        }
        if (!$user->two_factor_confirmed_at) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        $user->currentAccessToken()->delete();
        $token = $user->createToken('access', ['access'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('docente'),
            'must_change_password' => $user->must_change_password,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('docente'));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed|different:current_password',
        ]);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'La contraseña actual no es correcta'], 422);
        }

        $user->update(['password' => $request->password, 'must_change_password' => false]);
        return response()->json(['message' => 'Contraseña actualizada']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }
}
