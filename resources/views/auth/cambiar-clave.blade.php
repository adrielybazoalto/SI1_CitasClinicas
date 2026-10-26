<x-guest-layout>
    {{-- CU01 · Cambio obligatorio de la clave temporal antes de entrar al panel --}}
    <div class="mb-4 text-sm text-gray-600">
        Hola <strong>{{ auth()->user()->username }}</strong>: está usando una contraseña temporal.
        Debe definir una nueva contraseña para continuar.
    </div>

    <form method="POST" action="{{ route('password.cambiar.guardar') }}">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" value="Nueva contraseña (mínimo 8 caracteres)" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required minlength="8" autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>Guardar contraseña</x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-right">
        @csrf
        <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">Cerrar sesión</button>
    </form>
</x-guest-layout>
