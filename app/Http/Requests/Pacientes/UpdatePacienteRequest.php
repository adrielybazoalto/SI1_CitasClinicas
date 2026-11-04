<?php

namespace App\Http\Requests\Pacientes;

/**
 * CU05 · Edición de pacientes.
 *
 * Las reglas son las mismas del alta; StorePacienteRequest detecta el
 * modelo de la ruta y excluye sus propios valores de las validaciones unique.
 */
class UpdatePacienteRequest extends StorePacienteRequest
{
}
