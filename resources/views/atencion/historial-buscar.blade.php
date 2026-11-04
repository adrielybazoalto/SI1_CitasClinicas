{{-- CU22 · Buscar paciente para consultar su historial --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-purple-950 leading-tight">Historial del paciente</h2>
            <p class="text-xs text-purple-800/70 mt-0.5">Busque al paciente por CI, nombre, apellido o código</p>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <form method="GET" action="{{ route('historial.index') }}" id="form-buscar" class="bg-white p-4 sm:p-5 rounded-2xl border border-purple-100 shadow-xs">
                <x-input-label for="buscar" value="Paciente" class="text-xs font-semibold uppercase text-purple-950/80" />
                <div class="mt-1 flex gap-2">
                    <input id="buscar" name="buscar" type="search" value="{{ $buscar }}" minlength="2" maxlength="60" autofocus
                           placeholder="Ej.: 6543210 o López"
                           class="block w-full px-3.5 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20">
                    <button type="submit" class="px-5 py-2.5 bg-purple-800 hover:bg-purple-900 rounded-xl font-semibold text-xs text-white uppercase tracking-wider cursor-pointer">Buscar</button>
                </div>
                <p id="error-buscar" class="hidden mt-2 text-xs text-red-600">Escriba al menos 2 caracteres.</p>
            </form>

            @if ($pacientes !== null)
                <div class="bg-white rounded-2xl border border-purple-100 shadow-xs overflow-hidden">
                    <ul class="divide-y divide-purple-50">
                        @forelse ($pacientes as $paciente)
                            <li>
                                <a href="{{ route('historial.show', $paciente) }}" class="flex items-center justify-between px-5 py-4 hover:bg-purple-50/40 transition-colors">
                                    <div>
                                        <p class="text-sm font-semibold text-purple-950">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $paciente->codigo }} · CI {{ $paciente->ci }}{{ $paciente->estado ? '' : ' · inactivo' }}</p>
                                    </div>
                                    <span class="text-xs font-semibold text-purple-800">Ver historial →</span>
                                </a>
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center text-sm text-gray-500">No se encontraron pacientes con «{{ $buscar }}».</li>
                        @endforelse
                    </ul>
                    @if ($pacientes->hasPages())
                        <div class="px-5 py-4 border-t border-purple-100">{{ $pacientes->links() }}</div>
                    @endif
                </div>
            @elseif ($buscar !== '')
                <p class="text-sm text-gray-500">Escriba al menos 2 caracteres para buscar.</p>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('form-buscar').addEventListener('submit', function (e) {
            if (document.getElementById('buscar').value.trim().length < 2) {
                e.preventDefault();
                document.getElementById('error-buscar').classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
