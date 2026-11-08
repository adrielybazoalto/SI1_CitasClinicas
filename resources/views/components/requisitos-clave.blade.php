{{-- Política de contraseñas (App\Rules\ClaveSegura): lista de requisitos que se marca mientras se escribe.
     Validación en cliente: bloquea el envío con setCustomValidity; el servidor vuelve a validar.
     Uso: <x-requisitos-clave para="password" confirmacion="password_confirmation" /> --}}
@props(['para' => 'password', 'confirmacion' => null])
@php
    $longitud = (int) config('clinica.clave_min_longitud');
@endphp
<div {{ $attributes->merge(['class' => 'mt-2 p-3 bg-purple-50/60 border border-purple-100 rounded-xl text-xs']) }}
     x-data="{
        v: '',
        c: '',
        n: {{ $longitud }},
        get reglas() {
            return [
                { ok: [...this.v].length >= this.n, texto: 'Mínimo ' + this.n + ' caracteres' },
                { ok: /\p{Lu}/u.test(this.v), texto: 'Al menos una letra mayúscula (A-Z)' },
                { ok: /\p{Ll}/u.test(this.v), texto: 'Al menos una letra minúscula (a-z)' },
                { ok: /\d/.test(this.v), texto: 'Al menos un número (0-9)' },
                { ok: /[^\p{L}\p{N}\s]/u.test(this.v), texto: 'Al menos un carácter especial (@ # $ % & * ! ?)' },
                { ok: this.v !== '' && !/\s/.test(this.v), texto: 'Sin espacios' },
            ];
        },
        validar() {
            const clave = document.getElementById(@js($para));
            const conf = @js($confirmacion) ? document.getElementById(@js($confirmacion)) : null;
            const falla = this.reglas.find(r => !r.ok);
            clave?.setCustomValidity(this.v === '' || !falla ? '' : 'La contraseña no cumple: ' + falla.texto.toLowerCase() + '.');
            conf?.setCustomValidity(this.c === '' || this.c === this.v ? '' : 'Las contraseñas no coinciden.');
        },
     }"
     x-init="
        const clave = document.getElementById(@js($para));
        const conf = @js($confirmacion) ? document.getElementById(@js($confirmacion)) : null;
        clave?.addEventListener('input', e => { v = e.target.value; validar(); });
        conf?.addEventListener('input', e => { c = e.target.value; validar(); });
     ">
    <p class="font-semibold text-purple-950 mb-1.5">La contraseña debe cumplir con:</p>
    <ul class="grid gap-1 sm:grid-cols-2">
        <template x-for="regla in reglas" :key="regla.texto">
            <li class="flex items-center gap-1.5" :class="regla.ok ? 'text-emerald-700' : 'text-gray-600'">
                <span class="w-4 text-center font-bold" x-text="regla.ok ? '✓' : '•'" aria-hidden="true"></span>
                <span x-text="regla.texto"></span>
            </li>
        </template>
    </ul>
    @if ($confirmacion)
        <p x-show="c !== '' && c !== v" style="display: none;" class="mt-1.5 font-semibold text-red-700">Las contraseñas no coinciden.</p>
    @endif
    {{-- Sin JavaScript se lee igual la lista completa --}}
    <noscript>
        <ul class="list-disc pl-5 text-gray-600">
            <li>Mínimo {{ $longitud }} caracteres</li>
            <li>Al menos una letra mayúscula, una minúscula y un número</li>
            <li>Al menos un carácter especial (@ # $ % &amp; * ! ?), sin espacios</li>
        </ul>
    </noscript>
</div>
