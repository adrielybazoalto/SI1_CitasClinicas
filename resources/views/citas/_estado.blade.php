{{-- Insignia del estado de la cita (RF10) --}}
@php
    $colores = [
        'Programada' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Confirmada' => 'bg-sky-50 text-sky-700 border-sky-200',
        'Atendida' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Cancelada' => 'bg-gray-100 text-gray-600 border-gray-200',
        'Inasistencia' => 'bg-amber-50 text-amber-700 border-amber-200',
    ];
    $puntos = [
        'Programada' => 'bg-indigo-500',
        'Confirmada' => 'bg-sky-500',
        'Atendida' => 'bg-emerald-500',
        'Cancelada' => 'bg-gray-400',
        'Inasistencia' => 'bg-amber-500',
    ];
@endphp
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $colores[$estado] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $puntos[$estado] ?? 'bg-gray-400' }}"></span>
    {{ $estado }}
</span>
