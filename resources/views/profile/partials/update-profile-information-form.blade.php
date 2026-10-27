<section>
    <header>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-purple-950">
                Datos de la Cuenta
            </h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-900 border border-purple-200">
                Rol: {{ $user->rol?->nombre ?? 'Usuario' }}
            </span>
        </div>

        <p class="mt-1 text-sm text-gray-600">
            Actualice su nombre de usuario y su dirección de correo electrónico corporativo.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="username" value="Nombre de usuario" class="text-xs font-semibold uppercase text-purple-950/80" />
            <div class="relative mt-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input id="username" 
                       name="username" 
                       type="text" 
                       value="{{ old('username', $user->username) }}"
                       required 
                       minlength="4" 
                       maxlength="50" 
                       pattern="[a-zA-Z0-9._\-]+" 
                       autofocus 
                       autocomplete="username" 
                       class="block w-full pl-10 pr-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="correo" value="Correo electrónico" class="text-xs font-semibold uppercase text-purple-950/80" />
            <div class="relative mt-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <input id="correo" 
                       name="correo" 
                       type="email" 
                       value="{{ old('correo', $user->correo) }}"
                       required 
                       maxlength="100" 
                       autocomplete="email" 
                       class="block w-full pl-10 pr-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
            </div>
            <x-input-error class="mt-1.5" :messages="$errors->get('correo')" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button>Guardar Cambios</x-primary-button>

            @if (session('status') === 'profile-updated')
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
                    Datos guardados correctamente.
                </p>
            @endif
        </div>
    </form>
</section>
