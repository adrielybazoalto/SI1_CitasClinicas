<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Datos de la cuenta
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Actualice su nombre de usuario y su correo. Su rol: <strong>{{ $user->rol?->nombre }}</strong>.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="username" value="Nombre de usuario" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username)"
                          required minlength="4" maxlength="50" pattern="[a-zA-Z0-9._\-]+" autofocus autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="correo" value="Correo" />
            <x-text-input id="correo" name="correo" type="email" class="mt-1 block w-full" :value="old('correo', $user->correo)"
                          required maxlength="100" autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('correo')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Guardado.</p>
            @endif
        </div>
    </form>
</section>
