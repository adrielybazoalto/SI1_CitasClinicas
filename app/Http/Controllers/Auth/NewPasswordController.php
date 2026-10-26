<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU01 · Recuperar contraseña: define la nueva clave con el enlace recibido.
 */
class NewPasswordController extends Controller
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Valida el token (vigente y sin usar), guarda la nueva clave, quita el
     * bloqueo y marca el token como usado, todo en una transacción.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'correo' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $idUsuario = DB::transaction(function () use ($request) {
            $registro = DB::table('recuperacion_clave as r')
                ->join('usuario as u', 'u.id_usuario', '=', 'r.id_usuario')
                ->where('r.token_hash', hash('sha256', $request->token))
                ->whereNull('r.usado_en')
                ->where('r.expira_en', '>', now())
                ->whereRaw('lower(u.correo) = ?', [mb_strtolower(trim($request->correo))])
                ->where('u.estado', true)
                ->lockForUpdate()
                ->select('r.id_recuperacion', 'r.id_usuario')
                ->first();

            if (! $registro) {
                throw ValidationException::withMessages([
                    'correo' => 'El enlace no es válido o ya expiró. Solicite uno nuevo.',
                ]);
            }

            User::whereKey($registro->id_usuario)->update([
                'password_hash' => Hash::make($request->password),
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
                'debe_cambiar_clave' => false,
            ]);

            DB::table('recuperacion_clave')
                ->where('id_recuperacion', $registro->id_recuperacion)
                ->update(['usado_en' => now()]);

            return $registro->id_usuario;
        });

        $this->bitacora->registrar('CLAVE_RECUPERADA', 'usuario', $idUsuario, 'Contraseña restablecida por enlace de recuperación', $idUsuario);

        return redirect()->route('login')->with('status', 'Su contraseña fue restablecida. Ya puede iniciar sesión.');
    }
}
