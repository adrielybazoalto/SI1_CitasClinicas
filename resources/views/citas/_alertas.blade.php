{{-- Mensajes de éxito, error de negocio y errores de validación generales --}}
@if (session('status'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl shadow-xs flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm font-medium">{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 rounded-xl shadow-xs flex items-center gap-3">
        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.47 14.14A1 1 0 002.69 20h18.62a1 1 0 00.87-1.5L13.71 3.86a1 1 0 00-1.42 0z"/>
        </svg>
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
@endif

{{-- Errores de reglas del negocio que no corresponden a un campo visible --}}
@foreach (['cita', 'estado', 'id_paciente', 'motivo_cancelacion', 'detalle'] as $campo)
    @if ($errors->has($campo))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 rounded-xl shadow-xs flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.47 14.14A1 1 0 002.69 20h18.62a1 1 0 00.87-1.5L13.71 3.86a1 1 0 00-1.42 0z"/>
            </svg>
            <span class="text-sm font-medium">{{ $errors->first($campo) }}</span>
        </div>
    @endif
@endforeach
