<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-purple-950 tracking-tight">Registro de Cuenta</h1>
        <p class="text-sm text-gray-500 mt-1">Complete los datos para solicitar su registro en la clínica</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Nombre -->
        <div>
            <x-input-label for="name" value="Nombre completo" class="text-xs font-semibold uppercase text-purple-950/80" />
            <x-text-input id="name" class="block mt-1 w-full bg-purple-50/20 border-purple-200" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="ej. María López" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Correo Electrónico -->
        <div>
            <x-input-label for="email" value="Correo electrónico" class="text-xs font-semibold uppercase text-purple-950/80" />
            <x-text-input id="email" class="block mt-1 w-full bg-purple-50/20 border-purple-200" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="usuario@clinica.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Contraseña -->
        <div>
            <x-input-label for="password" value="Contraseña (mínimo 8 caracteres)" class="text-xs font-semibold uppercase text-purple-950/80" />
            <x-text-input id="password" class="block mt-1 w-full bg-purple-50/20 border-purple-200" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirmar Contraseña -->
        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" class="text-xs font-semibold uppercase text-purple-950/80" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full bg-purple-50/20 border-purple-200" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between pt-3">
            <a class="text-sm font-semibold text-purple-700 hover:text-purple-900 hover:underline transition-colors" href="{{ route('login') }}">
                ¿Ya tiene cuenta? Inicie sesión
            </a>

            <x-primary-button>
                Registrarse
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
