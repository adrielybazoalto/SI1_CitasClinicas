{{-- CU10 · Registrar indisponibilidad médica: periodos y registro de uno nuevo --}}
@php
    $claseCampo = 'mt-1 block w-full px-3.5 py-2.5 bg-white border border-purple-200 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20';
    $etiqueta = 'text-xs font-semibold text-gray-600';
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-encabezado-modulo />
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Indisponibilidad médica</h2>
        <p class="text-sm text-gray-500 mt-0.5">Ausencias del médico (congresos, vacaciones, emergencias). Esos horarios dejan de ofrecerse al instante.</p>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            @include('oferta._tabs')
            @include('citas._alertas')

            <form method="POST" action="{{ route('indisponibilidades.store') }}" id="form-indisponibilidad" novalidate
                  class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-purple-100">
                @csrf
                <h3 class="font-bold text-gray-900 mb-3">Registrar periodo</h3>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-3">
                        <label for="id_medico" class="{{ $etiqueta }}">Médico</label>
                        <select id="id_medico" name="id_medico" required class="{{ $claseCampo }}">
                            <option value="">Seleccione…</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id_medico }}" @selected((int) old('id_medico', $medicoInicial) === $medico->id_medico)>{{ $medico->nombre_completo }}{{ $medico->estado ? '' : ' (inactivo)' }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_medico')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="inicio" class="{{ $etiqueta }}">Desde</label>
                        <input id="inicio" name="inicio" type="datetime-local" required value="{{ old('inicio') }}" class="{{ $claseCampo }}">
                        <x-input-error :messages="$errors->get('inicio')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="fin" class="{{ $etiqueta }}">Hasta</label>
                        <input id="fin" name="fin" type="datetime-local" required value="{{ old('fin') }}" class="{{ $claseCampo }}">
                        <x-input-error :messages="$errors->get('fin')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-3">
                        <label for="motivo" class="{{ $etiqueta }}">Motivo</label>
                        <input id="motivo" name="motivo" type="text" required maxlength="150" value="{{ old('motivo') }}" placeholder="Ej.: Congreso de cardiología" class="{{ $claseCampo }}">
                        <x-input-error :messages="$errors->get('motivo')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full px-4 py-2.5 bg-purple-800 hover:bg-purple-900 text-white text-sm rounded-xl font-semibold cursor-pointer">Registrar</button>
                    </div>
                </div>
                <p id="error-indisponibilidad" class="hidden mt-2 text-sm text-rose-600"></p>
            </form>

            <div class="bg-white rounded-2xl shadow-sm border border-purple-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-purple-100 text-sm">
                        <thead class="bg-purple-50/60">
                            <tr>
                                @foreach (['Médico', 'Desde', 'Hasta', 'Motivo', 'Registró', ''] as $columna)
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ $columna }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                            @forelse ($periodos as $periodo)
                                @php $pasado = $periodo->fin->isPast(); @endphp
                                <tr class="{{ $pasado ? 'text-gray-400' : '' }}">
                                    <td class="px-4 py-3 font-semibold {{ $pasado ? '' : 'text-gray-900' }}">{{ $periodo->medico?->nombre_completo }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $periodo->inicio->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $periodo->fin->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3">{{ $periodo->motivo }}</td>
                                    <td class="px-4 py-3">{{ $periodo->registrador?->username ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @unless ($pasado)
                                            <a href="{{ route('indisponibilidades.citas', $periodo) }}" class="text-xs font-semibold text-purple-800 hover:underline">Citas afectadas →</a>
                                        @else
                                            <span class="text-xs">Terminado</span>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay periodos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($periodos->hasPages())
                    <div class="px-4 py-3 border-t border-purple-100">{{ $periodos->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.getElementById('form-indisponibilidad').addEventListener('submit', function (e) {
            const v = (id) => document.getElementById(id).value.trim();
            let mensaje = '';
            if (!v('id_medico')) mensaje = 'Seleccione el médico.';
            else if (!v('inicio') || !v('fin')) mensaje = 'Indique el inicio y el fin del periodo.';
            else if (v('fin') <= v('inicio')) mensaje = 'El fin debe ser posterior al inicio.';
            else if (new Date(v('fin')) <= new Date()) mensaje = 'El periodo ya terminó.';
            else if (v('motivo').length < 3) mensaje = 'Indique el motivo.';
            if (mensaje) {
                e.preventDefault();
                const error = document.getElementById('error-indisponibilidad');
                error.textContent = mensaje;
                error.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
