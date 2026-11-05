{{-- CU06 · Gestionar antecedentes clínicos del paciente --}}
@php
    $volver = ($volverCita ?? null)
        ? route('atencion.create', $volverCita)
        : route('pacientes.index', ['accion' => 'antecedentes']);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ $volver }}" class="p-2 text-purple-700 hover:text-purple-900 hover:bg-purple-50 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <x-encabezado-modulo />
                <h2 class="text-3xl font-bold text-gray-900 leading-tight">Antecedentes clínicos</h2>
                <p class="text-sm text-gray-500">
                    {{ $paciente->apellidos }}, {{ $paciente->nombres }} · {{ $paciente->codigo }} · CI {{ $paciente->ci }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl shadow-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-xs">
                    <div class="flex items-center gap-2 font-semibold text-sm mb-1">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Por favor verifique los siguientes errores:
                    </div>
                    @foreach ($errors->all() as $error)
                        <div class="text-xs ml-6">• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Resumen del paciente --}}
            <div class="bg-purple-50/60 border border-purple-200/80 rounded-2xl p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800/70">Paciente</p>
                    <p class="text-sm font-semibold text-purple-950 mt-0.5">{{ $paciente->apellidos }}, {{ $paciente->nombres }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800/70">Documento</p>
                    <p class="text-sm font-semibold text-purple-950 mt-0.5">{{ $paciente->ci }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-purple-800/70">Teléfono</p>
                    <p class="text-sm font-semibold text-purple-950 mt-0.5">{{ $paciente->telefono }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('pacientes.antecedentes.update', $paciente) }}"
                  class="bg-white p-6 sm:p-8 shadow-xs border border-purple-100 rounded-2xl sm:rounded-3xl space-y-6">
                @csrf
                @method('PUT')
                @if ($volverCita ?? null)
                    <input type="hidden" name="volver_cita" value="{{ $volverCita->id_cita }}">
                @endif

                <div class="flex items-center gap-2 border-b border-purple-100 pb-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    <h3 class="font-bold text-lg text-purple-950">Ficha clínica básica</h3>
                </div>

                @include('pacientes._antecedente-fields')

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-purple-100/80">
                    <a href="{{ $volver }}"
                       class="px-4 py-2.5 text-xs font-semibold text-purple-800 hover:text-purple-950 hover:bg-purple-50 rounded-xl transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-800 hover:bg-purple-900 active:bg-purple-950 rounded-xl font-semibold text-xs text-white uppercase tracking-wider shadow-sm hover:shadow-md hover:shadow-purple-900/20 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-all duration-150 cursor-pointer">
                        <span>Guardar Antecedentes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
