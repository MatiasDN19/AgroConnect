import { avisar } from '@/Components/Avisos/avisos';
import {
    Bloque,
    BotonGuardar,
    Campo,
    CLASES_CAMPO,
} from '@/Components/Formulario';
import { useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

export default function UpdatePasswordForm() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, put, reset, processing } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

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
            <form onSubmit={updatePassword} className="space-y-4">
                <Campo
                    id="current_password"
                    etiqueta="Contraseña actual"
                    error={errors.current_password}
                >
                    <input
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        onChange={(e) =>
                            setData('current_password', e.target.value)
                        }
                        type="password"
                        autoComplete="current-password"
                        className={CLASES_CAMPO}
                    />
                </Campo>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Campo
                        id="password"
                        etiqueta="Contraseña nueva"
                        error={errors.password}
                    >
                        <input
                            id="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            type="password"
                            autoComplete="new-password"
                            className={CLASES_CAMPO}
                        />
                    </Campo>

                    <Campo
                        id="password_confirmation"
                        etiqueta="Repetí la contraseña nueva"
                        error={errors.password_confirmation}
                    >
                        <input
                            id="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            type="password"
                            autoComplete="new-password"
                            className={CLASES_CAMPO}
                        />
                    </Campo>
                </div>

                <div className="flex justify-end pt-1">
                    <BotonGuardar
                        disabled={
                            processing ||
                            !data.current_password ||
                            !data.password
                        }
                    >
                        Cambiar contraseña
                    </BotonGuardar>
                </div>
            </form>
        </Bloque>
    );
}
