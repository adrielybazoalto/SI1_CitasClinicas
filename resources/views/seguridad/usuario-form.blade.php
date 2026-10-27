{{-- CU02 · Alta y edición de usuario (la clave solo se define en el alta) --}}
@php($esAlta = ! $usuario->exists)
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.index') }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-purple-950 leading-tight">
                    {{ $esAlta ? 'Nuevo usuario' : 'Editar usuario: '.$usuario->username }}
                </h2>
                <p class="text-xs text-purple-800/70">{{ $esAlta ? 'Complete la información para dar de alta una cuenta' : 'Modifique los datos y el rol asignado a la cuenta' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" class="bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl space-y-5"
                  action="{{ $esAlta ? route('usuarios.store') : route('usuarios.update', $usuario) }}">
                @csrf
                @unless ($esAlta)
                    @method('PUT')
                @endunless

                <!-- Nombre de usuario -->
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
                               required
                               minlength="4" 
                               maxlength="50" 
                               pattern="[a-zA-Z0-9._\-]+"
                               value="{{ old('username', $usuario->username) }}" 
                               autofocus 
                               placeholder="ej. jperez"
                               class="block w-full pl-10 pr-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    </div>
                    <x-input-error :messages="$errors->get('username')" class="mt-1.5" />
                </div>

                <!-- Correo -->
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
                               required 
                               maxlength="100"
                               value="{{ old('correo', $usuario->correo) }}" 
                               placeholder="usuario@clinica.com"
                               class="block w-full pl-10 pr-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                    </div>
                    <x-input-error :messages="$errors->get('correo')" class="mt-1.5" />
                </div>

                <!-- Rol -->
                <div>
                    <x-input-label for="id_rol" value="Rol en la clínica" class="text-xs font-semibold uppercase text-purple-950/80" />
                    <div class="relative mt-1">
                        <select id="id_rol" 
                                name="id_rol" 
                                required 
                                class="block w-full py-2.5 px-3 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
                            <option value="">Seleccione un rol…</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->id_rol }}" @selected(old('id_rol', $usuario->id_rol) == $rol->id_rol)>{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input-error :messages="$errors->get('id_rol')" class="mt-1.5" />
                </div>

                @if ($esAlta)
                    <!-- Contraseña -->
                    <div x-data="{ show: false }">
                        <x-input-label for="password" value="Contraseña (mínimo 8 caracteres)" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password" 
                                   :type="show ? 'text' : 'password'" 
                                   name="password" 
                                   required 
                                   minlength="8" 
                                   autocomplete="new-password" 
                                   placeholder="••••••••"
                                   class="block w-full pl-10 pr-10 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <button type="button" 
                                    @click="show = !show" 
                                    tabindex="-1"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-purple-400 hover:text-purple-700 transition-colors">
                                <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="show" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div x-data="{ showConf: false }">
                        <x-input-label for="password_confirmation" value="Confirmar contraseña" class="text-xs font-semibold uppercase text-purple-950/80" />
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-purple-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password_confirmation" 
                                   :type="showConf ? 'text' : 'password'" 
                                   name="password_confirmation" 
                                   required 
                                   minlength="8" 
                                   autocomplete="new-password" 
                                   placeholder="••••••••"
                                   class="block w-full pl-10 pr-10 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
                            <button type="button" 
                                    @click="showConf = !showConf" 
                                    tabindex="-1"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-purple-400 hover:text-purple-700 transition-colors">
                                <svg x-show="!showConf" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showConf" style="display: none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Exigir cambio de clave -->
                    <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input type="hidden" name="debe_cambiar_clave" value="0">
                            <input type="checkbox" 
                                   name="debe_cambiar_clave" 
                                   value="1" 
                                   class="rounded-lg border-purple-300 text-purple-700 focus:ring-purple-600 w-4 h-4" 
                                   @checked(old('debe_cambiar_clave', '1') == '1')>
                            <span class="text-xs sm:text-sm font-medium text-purple-950">
                                Exigir cambio de contraseña en el primer inicio de sesión
                            </span>
                        </label>
                    </div>
                @endif

                <!-- Acciones del Formulario -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-purple-100/80">
                    <a href="{{ route('usuarios.index') }}" 
                       class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 cursor-pointer">
                        <span>{{ $esAlta ? 'Registrar Usuario' : 'Guardar Cambios' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
