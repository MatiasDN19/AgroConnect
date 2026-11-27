import { avisar } from '@/Components/Avisos/avisos';
import {
    Bloque,
    BotonGuardar,
    Campo,
    clasesCampo,
} from '@/Components/Formulario';
import ReglasContrasena from '@/Components/ReglasContrasena';
import {
    contrasenaValida,
    MENSAJES,
    useValidacion,
} from '@/utils/validaciones';
import { useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

export default function UpdatePasswordForm() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, put, reset, processing, clearErrors } =
        useForm({
            current_password: '',
            password: '',
            password_confirmation: '',
        });

    // Validación en vivo (KAN-313). La contraseña actual solo la puede
    // confirmar el servidor: acá solo se pide que no esté vacía.
    type Clave = 'current_password' | 'password' | 'password_confirmation';
    const { estado, error, tocar, todoValido } = useValidacion<Clave>(
        {
            current_password: {
                valor: data.current_password,
                valido: data.current_password !== '',
                mensaje: MENSAJES.requerido,
            },
            password: {
                valor: data.password,
                valido: contrasenaValida(data.password),
                mensaje: MENSAJES.contrasena,
            },
            password_confirmation: {
                valor: data.password_confirmation,
                valido:
                    data.password_confirmation !== '' &&
                    data.password_confirmation === data.password,
                mensaje:
                    data.password_confirmation === ''
                        ? MENSAJES.repetir
                        : MENSAJES.confirmacion,
            },
        },
        errors,
    );
    const cambiar = (campo: Clave, valor: string) => {
        setData(campo, valor);
        if (errors[campo]) clearErrors(campo);
    };
    // La contraseña actual no se pinta de verde: que no esté vacía no
    // significa que sea la correcta.
    const estadoActual =
        estado('current_password') === 'error' ? 'error' : null;

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();
        if (!todoValido()) return;

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                avisar('exito', 'Cambiaste tu contraseña.');
            },
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <Bloque
            Icono={ShieldCheck}
            titulo="Cambiar contraseña"
            descripcion="Usá una contraseña larga y difícil de adivinar para mantener tu cuenta segura."
        >
            <form onSubmit={updatePassword} noValidate className="space-y-4">
                <Campo
                    id="current_password"
                    etiqueta="Contraseña actual"
                    error={error('current_password')}
                >
                    <input
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        onChange={(e) =>
                            cambiar('current_password', e.target.value)
                        }
                        onBlur={() => tocar('current_password')}
                        type="password"
                        autoComplete="current-password"
                        aria-invalid={estadoActual === 'error' || undefined}
                        aria-describedby="current_password-error"
                        className={clasesCampo(estadoActual)}
                    />
                </Campo>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Campo
                        id="password"
                        etiqueta="Contraseña nueva"
                        error={error('password')}
                    >
                        <input
                            id="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                cambiar('password', e.target.value)
                            }
                            onBlur={() => tocar('password')}
                            type="password"
                            autoComplete="new-password"
                            aria-invalid={
                                estado('password') === 'error' || undefined
                            }
                            aria-describedby="reglas-contrasena-perfil password-error"
                            className={clasesCampo(estado('password'))}
                        />
                    </Campo>

                    <Campo
                        id="password_confirmation"
                        etiqueta="Repetí la contraseña nueva"
                        error={error('password_confirmation')}
                    >
                        <input
                            id="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                cambiar('password_confirmation', e.target.value)
                            }
                            onBlur={() => tocar('password_confirmation')}
                            type="password"
                            autoComplete="new-password"
                            aria-invalid={
                                estado('password_confirmation') === 'error' ||
                                undefined
                            }
                            aria-describedby="password_confirmation-error"
                            className={clasesCampo(
                                estado('password_confirmation'),
                            )}
                        />
                    </Campo>
                </div>

                {/* Reglas y fortaleza de la contraseña nueva (KAN-313). */}
                <div id="reglas-contrasena-perfil">
                    <ReglasContrasena contrasena={data.password} />
                </div>

                <div className="flex justify-end pt-1">
                    <BotonGuardar disabled={processing}>
                        Cambiar contraseña
                    </BotonGuardar>
                </div>
            </form>
        </Bloque>
    );
}
