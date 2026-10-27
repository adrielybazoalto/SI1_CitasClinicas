<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-purple-950 tracking-tight">Recuperar Contraseña</h1>
        <p class="text-sm text-gray-600 mt-2">
            Ingrese el correo electrónico asociado a su cuenta y le enviaremos un enlace para restablecer su contraseña.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="correo" value="Correo electrónico" />
            <div class="relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <input id="correo" 
                       type="email" 
                       name="correo" 
                       value="{{ old('correo') }}" 
                       required 
                       autofocus 
                       maxlength="100"
                       placeholder="usuario@clinica.com"
                       class="block w-full pl-11 pr-4 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
            </div>
            <x-input-error :messages="$errors->get('correo')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm font-semibold text-purple-700 hover:text-purple-900 hover:underline transition-colors flex items-center gap-1" href="{{ route('login') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al inicio de sesión
            </a>
            <x-primary-button>
                Enviar enlace
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
