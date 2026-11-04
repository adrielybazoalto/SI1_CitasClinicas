# Contraseñas de prueba - Clínica Vida Salud

Regla: **contraseña = username + `@2026`**. Se puede entrar con el username o con el correo.

| # | Rol | Username | Correo | Contraseña | Nota |
|---|---|---|---|---|---|
| 1 | Administrador | admin | admin@vidasalud.bo | *(la que se definió al cambiarla el 04/11)* | Creado por `SeguridadSeeder`; `DatosDemoSeeder` no lo modifica |
| 2 | Recepcionista | rmendoza | rosa.mendoza@vidasalud.bo | `rmendoza@2026` |  |
| 3 | Recepcionista | jcuellar | jorge.cuellar@vidasalud.bo | `jcuellar@2026` | Debe cambiarla al entrar (debe_cambiar_clave = TRUE) |
| 4 | Recepcionista | lsuarez | lucia.suarez@vidasalud.bo | `lsuarez@2026` |  |
| 5 | Médico | cperez | carlos.perez@vidasalud.bo | `cperez@2026` |  |
| 6 | Médico | avaca | ana.vaca@vidasalud.bo | `avaca@2026` |  |
| 7 | Médico | lgutierrez | luis.gutierrez@vidasalud.bo | `lgutierrez@2026` |  |
| 8 | Médico | psaucedo | patricia.saucedo@vidasalud.bo | `psaucedo@2026` |  |
| 9 | Médico | ranez | roberto.anez@vidasalud.bo | `ranez@2026` |  |
| 10 | Médico | drivero | daniela.rivero@vidasalud.bo | `drivero@2026` |  |
| 11 | Paciente | p6543210 | maria.lopez@gmail.com | `p6543210@2026` |  |
| 12 | Paciente | p7123456 | jc.flores@gmail.com | `p7123456@2026` | Tiene 2 intentos fallidos registrados |
| 13 | Paciente | p5987654 | carla.rojas@hotmail.com | `p5987654@2026` |  |
| 14 | Paciente | p8234567 | miguel.cespedes@gmail.com | `p8234567@2026` |  |
| 15 | Paciente | p6345678 | valeria.montano@gmail.com | `p6345678@2026` |  |
| 16 | Paciente | p9456789 | fam.arancibia@gmail.com | `p9456789@2026` |  |
| 17 | Paciente | p4567890 | 4567890@sincorreo.vidasalud.bo | `p4567890@2026` |  |
| 18 | Paciente | p7678901 | fernando.ortiz@gmail.com | `p7678901@2026` |  |
| 19 | Paciente | p6789012 | gabi.justiniano@gmail.com | `p6789012@2026` |  |
| 20 | Paciente | p5890123 | andres.melgar@gmail.com | `p5890123@2026` |  |
| 21 | Paciente | p8901234 | fam.paz@gmail.com | `p8901234@2026` | Debe cambiarla al entrar (debe_cambiar_clave = TRUE) |
| 22 | Paciente | p7012345 | 7012345@sincorreo.vidasalud.bo | `p7012345@2026` |  |
| 23 | Paciente | p9123456 | sofia.romero@gmail.com | `p9123456@2026` |  |

En la base de datos se guarda solo el hash BCRYPT (`password_hash()` de PHP); el login lo compara con `password_verify()`.
