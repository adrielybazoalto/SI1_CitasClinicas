<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * CU01 · Recuperar contraseña: envía un enlace de un solo uso.
 * El token se guarda cifrado (sha256) en la tabla recuperacion_clave.
 */
class PasswordResetLinkController extends Controller
{
    /** Minutos de validez del enlace. */
    private const VALIDEZ_MIN = 60;

    public function __construct(private BitacoraService $bitacora)
    {
    }

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Siempre responde el mismo mensaje para no revelar qué correos existen.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['correo' => ['required', 'email', 'max:100']]);

        $usuario = User::whereRaw('lower(correo) = ?', [mb_strtolower(trim($request->correo))])
            ->where('estado', true)
            ->first();

        if ($usuario) {
            $token = Str::random(64);

            // Invalida enlaces anteriores sin usar y crea el nuevo.
            DB::table('recuperacion_clave')
                ->where('id_usuario', $usuario->id_usuario)
                ->whereNull('usado_en')
                ->update(['usado_en' => now()]);

            DB::table('recuperacion_clave')->insert([
                'id_usuario' => $usuario->id_usuario,
                'token_hash' => hash('sha256', $token),
                'expira_en' => now()->addMinutes(self::VALIDEZ_MIN),
                'creado_en' => now(),
            ]);

            $enlace = route('password.reset', ['token' => $token, 'correo' => $usuario->correo]);

            try {
                Mail::raw(
                    "Hola {$usuario->username}:\n\nPara restablecer su contraseña de Vida Salud ingrese al siguiente enlace "
                    ."(válido por ".self::VALIDEZ_MIN." minutos):\n\n{$enlace}\n\nSi usted no lo solicitó, ignore este mensaje.",
                    fn ($m) => $m->to($usuario->correo)->subject('Vida Salud · Recuperar contraseña')
                );
            } catch (Throwable $e) {
                Log::error('No se pudo enviar el correo de recuperación', ['error' => $e->getMessage()]);
            }

            $this->bitacora->registrar('RECUPERACION_SOLICITADA', 'usuario', $usuario->id_usuario, 'Enlace de recuperación enviado', $usuario->id_usuario);
        }

        return back()->with('status', 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.');
    }
}
