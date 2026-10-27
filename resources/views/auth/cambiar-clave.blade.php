<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="w-12 h-12 bg-purple-100 text-purple-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-purple-950 tracking-tight">Cambio de Contraseña</h1>
        <div class="mt-3 p-3 bg-purple-50/80 border border-purple-200 text-purple-900 rounded-xl text-xs sm:text-sm text-left">
            Hola <strong>{{ auth()->user()->username }}</strong>: está usando una contraseña temporal asignada por el sistema. Por seguridad, debe definir una nueva contraseña personal para continuar al panel.
        </div>
    </div>

    <form method="POST" action="{{ route('password.cambiar.guardar') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div x-data="{ show: false }">
            <x-input-label for="password" value="Nueva contraseña (mínimo 8 caracteres)" />
            <div class="relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input id="password" 
                       :type="show ? 'text' : 'password'" 
                       name="password" 
                       required 
                       minlength="8" 
                       autofocus 
                       autocomplete="new-password"
                       placeholder="Mínimo 8 caracteres"
                       class="block w-full pl-11 pr-11 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                <button type="button" 
                        @click="show = !show" 
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-purple-400 hover:text-purple-700 transition-colors">
                    <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" style="display: none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div x-data="{ showConf: false }">
            <x-input-label for="password_confirmation" value="Confirmar nueva contraseña" />
            <div class="relative rounded-xl shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input id="password_confirmation" 
                       :type="showConf ? 'text' : 'password'" 
                       name="password_confirmation" 
                       required 
                       minlength="8" 
                       autocomplete="new-password"
                       placeholder="Repita la nueva contraseña"
                       class="block w-full pl-11 pr-11 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                <button type="button" 
                        @click="showConf = !showConf" 
                        tabindex="-1"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-purple-400 hover:text-purple-700 transition-colors">
                    <svg x-show="!showConf" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showConf" style="display: none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Guardar contraseña
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
        @csrf
        <button type="submit" class="text-xs font-semibold text-gray-500 hover:text-purple-800 hover:underline transition-colors">
            Cerrar sesión e intentar más tarde
        </button>
    </form>
</x-guest-layout>
