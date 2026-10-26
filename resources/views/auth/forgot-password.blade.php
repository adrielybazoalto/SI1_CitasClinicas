<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Ingrese el correo de su cuenta y le enviaremos un enlace para definir una nueva contraseña.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="correo" value="Correo" />
            <x-text-input id="correo" class="block mt-1 w-full" type="email" name="correo" :value="old('correo')" required autofocus maxlength="100" />
            <x-input-error :messages="$errors->get('correo')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">Volver</a>
            <x-primary-button>
                Enviar enlace
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
