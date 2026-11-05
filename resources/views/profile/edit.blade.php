<x-app-layout>
    <x-slot name="header">
        <div>
            <x-encabezado-modulo />
            <h2 class="text-3xl font-bold text-gray-900 leading-tight">
                Mi Perfil
            </h2>
            <p class="text-sm text-gray-500">Administre sus datos personales y credenciales de acceso</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Información del Perfil -->
            <div class="p-6 sm:p-8 bg-white shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Actualizar Contraseña -->
            <div class="p-6 sm:p-8 bg-white shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Sin opción de eliminar cuenta: borrado lógico estricto (RN11) --}}
        </div>
    </div>
</x-app-layout>
