<x-guest-layout>
    <!-- Encabezado de la tarjeta -->
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-purple-950 tracking-tight">Iniciar Sesión</h1>
        <p class="text-sm text-gray-500 mt-1">Ingrese sus credenciales para acceder al portal</p>
    </div>

    <!-- Mensaje de estado (p. ej. contraseña restablecida) -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" novalidate class="space-y-5">
        @csrf

        <!-- Usuario o correo -->
        <div>
            <x-input-label for="login" value="Usuario o correo" />
            <div class="relative rounded-xl shadow-sm">
                <!-- Icono sobre de correo a la izquierda -->
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>

                <input id="login" 
                       type="text" 
                       name="login" 
                       value="{{ old('login') }}" 
                       required 
                       autofocus 
                       autocomplete="username" 
                       maxlength="100"
                       placeholder="ej. jperez o usuario@clinica.com"
                       class="block w-full pl-11 pr-4 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
            </div>
            <x-input-error :messages="$errors->get('login')" class="mt-1.5" />
        </div>

        <!-- Contraseña con icono candado y ojo para mostrar/ocultar -->
        <div x-data="{ showPass: false }">
            <x-input-label for="password" value="Contraseña" />
            <div class="relative rounded-xl shadow-sm">
                <!-- Icono de seguridad a la izquierda -->
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>

                <!-- Campo de contraseña interactivo -->
                <input id="password" 
                       :type="showPass ? 'text' : 'password'" 
                       name="password" 
                       required 
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="block w-full pl-11 pr-11 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />

                <!-- Botón con ojo para mostrar/ocultar contraseña a la derecha -->
                <button type="button" 
                        @click="showPass = !showPass" 
                        tabindex="-1"
                        title="Mostrar u ocultar contraseña"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-purple-400 hover:text-purple-700 focus:outline-none focus:text-purple-800 transition-colors cursor-pointer">
                    <!-- Ojo abierto (cuando la contraseña está oculta) -->
                    <svg x-show="!showPass" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <!-- Ojo tachado (cuando la contraseña es visible) -->
                    <svg x-show="showPass" style="display: none;" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Enlace de recuperación de contraseña -->
        <div class="flex items-center justify-end pt-1">
            <a class="text-sm font-medium text-purple-700 hover:text-purple-900 hover:underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-600 transition-colors" 
               href="{{ route('password.request') }}">
                ¿Olvidó su contraseña?
            </a>
        </div>

        <!-- Botón de acción principal: INICIAR SESIÓN -->
        <div class="pt-2">
            <button type="submit" 
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 focus:bg-purple-900 text-white font-bold rounded-xl text-sm uppercase tracking-wider shadow-md shadow-purple-950/20 hover:shadow-lg hover:shadow-purple-900/30 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-200 cursor-pointer">
                <span>INICIAR SESIÓN</span>
                <svg class="w-4 h-4 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </form>
</x-guest-layout>
