<section>
    <header>
        <h2 class="text-lg font-bold text-purple-950">
            Actualizar Contraseña
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Asegúrese de que su cuenta utilice una contraseña segura de al menos 8 caracteres para proteger su acceso.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Contraseña actual" class="text-xs font-semibold uppercase text-purple-950/80" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full bg-purple-50/20 border-purple-200" autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Nueva contraseña" class="text-xs font-semibold uppercase text-purple-950/80" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full bg-purple-50/20 border-purple-200" autocomplete="new-password" placeholder="Mínimo 8 caracteres" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirmar nueva contraseña" class="text-xs font-semibold uppercase text-purple-950/80" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full bg-purple-50/20 border-purple-200" autocomplete="new-password" placeholder="Repita la nueva contraseña" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button>Guardar Contraseña</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm font-semibold text-emerald-700 flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Contraseña actualizada correctamente.
                </p>
            @endif
        </div>
    </form>
</section>
