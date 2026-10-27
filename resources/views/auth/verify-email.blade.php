<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-purple-950 tracking-tight">Verificación de Correo</h1>
        <p class="text-sm text-gray-600 mt-2">
            ¡Gracias por formar parte de Vida Salud! Antes de comenzar, por favor verifique su dirección de correo electrónico haciendo clic en el enlace que acabamos de enviarle. Si no recibió el correo, con gusto le enviaremos otro.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-semibold text-sm text-emerald-700 bg-emerald-50 p-3 rounded-xl border border-emerald-200">
            Se ha enviado un nuevo enlace de verificación a la dirección de correo proporcionada.
        </div>
    @endif

    <div class="mt-5 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Reenviar correo
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm font-semibold text-gray-600 hover:text-purple-900 hover:underline transition-colors">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
