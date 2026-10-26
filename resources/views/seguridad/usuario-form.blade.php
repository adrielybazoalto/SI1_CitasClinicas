{{-- CU02 · Alta y edición de usuario (la clave solo se define en el alta) --}}
@php($esAlta = ! $usuario->exists)
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $esAlta ? 'Nuevo usuario' : 'Editar usuario: '.$usuario->username }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4"
                  action="{{ $esAlta ? route('usuarios.store') : route('usuarios.update', $usuario) }}">
                @csrf
                @unless ($esAlta)
                    @method('PUT')
                @endunless

                <div>
                    <x-input-label for="username" value="Nombre de usuario" />
                    <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" required
                                  minlength="4" maxlength="50" pattern="[a-zA-Z0-9._\-]+"
                                  :value="old('username', $usuario->username)" autofocus />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="correo" value="Correo" />
                    <x-text-input id="correo" name="correo" type="email" class="mt-1 block w-full" required maxlength="100"
                                  :value="old('correo', $usuario->correo)" />
                    <x-input-error :messages="$errors->get('correo')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="id_rol" value="Rol" />
                    <select id="id_rol" name="id_rol" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">Seleccione…</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id_rol }}" @selected(old('id_rol', $usuario->id_rol) == $rol->id_rol)>{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('id_rol')" class="mt-2" />
                </div>

                @if ($esAlta)
                    <div>
                        <x-input-label for="password" value="Contraseña (mínimo 8 caracteres)" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required minlength="8" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required minlength="8" autocomplete="new-password" />
                    </div>

                    <label class="inline-flex items-center">
                        <input type="hidden" name="debe_cambiar_clave" value="0">
                        <input type="checkbox" name="debe_cambiar_clave" value="1" class="rounded border-gray-300" @checked(old('debe_cambiar_clave', '1') == '1')>
                        <span class="ms-2 text-sm text-gray-700">Exigir cambio de contraseña en el primer inicio de sesión</span>
                    </label>
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('usuarios.index') }}" class="px-4 py-2 text-sm text-gray-700 hover:underline">Cancelar</a>
                    <x-primary-button>{{ $esAlta ? 'Registrar' : 'Guardar cambios' }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
