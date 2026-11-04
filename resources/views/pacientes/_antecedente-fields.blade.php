{{-- CU06 · Campos de antecedentes clínicos (reutilizados en alta/edición y en la pantalla dedicada) --}}
@php($antecedente = $paciente->exists ? $paciente->antecedente : null)

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <x-input-label for="tipo_sangre" value="Tipo de sangre" class="text-xs font-semibold uppercase text-purple-950/80" />
        <select id="tipo_sangre" name="tipo_sangre"
                class="mt-1 block w-full py-2.5 px-3 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-800 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">
            <option value="">Sin dato</option>
            @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $tipo)
                <option value="{{ $tipo }}" @selected(old('tipo_sangre', $antecedente?->tipo_sangre) === $tipo)>{{ $tipo }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo_sangre')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="contacto_telefono" value="Teléfono de emergencia" class="text-xs font-semibold uppercase text-purple-950/80" />
        <input id="contacto_telefono" name="contacto_telefono" type="text" maxlength="20"
               value="{{ old('contacto_telefono', $antecedente?->contacto_telefono) }}"
               placeholder="Ej. 70012345"
               class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
        <x-input-error :messages="$errors->get('contacto_telefono')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="contacto_nombre" value="Contacto de emergencia" class="text-xs font-semibold uppercase text-purple-950/80" />
        <input id="contacto_nombre" name="contacto_nombre" type="text" maxlength="100"
               value="{{ old('contacto_nombre', $antecedente?->contacto_nombre) }}"
               placeholder="Nombre completo"
               class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
        <x-input-error :messages="$errors->get('contacto_nombre')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="contacto_parentesco" value="Parentesco" class="text-xs font-semibold uppercase text-purple-950/80" />
        <input id="contacto_parentesco" name="contacto_parentesco" type="text" maxlength="30"
               value="{{ old('contacto_parentesco', $antecedente?->contacto_parentesco) }}"
               placeholder="Ej. Madre, cónyuge, hermano"
               class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150" />
        <x-input-error :messages="$errors->get('contacto_parentesco')" class="mt-1.5" />
    </div>
</div>

<div class="grid grid-cols-1 gap-5">
    <div>
        <x-input-label for="alergias" value="Alergias" class="text-xs font-semibold uppercase text-purple-950/80" />
        <textarea id="alergias" name="alergias" rows="3" maxlength="2000"
                  placeholder="Medicamentos, alimentos u otras alergias"
                  class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">{{ old('alergias', $antecedente?->alergias) }}</textarea>
        <x-input-error :messages="$errors->get('alergias')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="enfermedades_cronicas" value="Enfermedades crónicas" class="text-xs font-semibold uppercase text-purple-950/80" />
        <textarea id="enfermedades_cronicas" name="enfermedades_cronicas" rows="3" maxlength="2000"
                  placeholder="Diabetes, hipertensión, asma, etc."
                  class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">{{ old('enfermedades_cronicas', $antecedente?->enfermedades_cronicas) }}</textarea>
        <x-input-error :messages="$errors->get('enfermedades_cronicas')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="medicamentos" value="Medicamentos actuales" class="text-xs font-semibold uppercase text-purple-950/80" />
        <textarea id="medicamentos" name="medicamentos" rows="3" maxlength="2000"
                  placeholder="Medicamentos que toma actualmente"
                  class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">{{ old('medicamentos', $antecedente?->medicamentos) }}</textarea>
        <x-input-error :messages="$errors->get('medicamentos')" class="mt-1.5" />
    </div>

    <div>
        <x-input-label for="antecedentes" value="Otros antecedentes" class="text-xs font-semibold uppercase text-purple-950/80" />
        <textarea id="antecedentes" name="antecedentes" rows="3" maxlength="2000"
                  placeholder="Cirugías, hospitalizaciones, hábitos, etc."
                  class="mt-1 block w-full px-3 py-2.5 bg-purple-50/20 border border-purple-200/90 rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 transition-all duration-150">{{ old('antecedentes', $antecedente?->antecedentes) }}</textarea>
        <x-input-error :messages="$errors->get('antecedentes')" class="mt-1.5" />
    </div>
</div>
