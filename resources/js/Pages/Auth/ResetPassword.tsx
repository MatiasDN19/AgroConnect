import {
    BotonAuth,
    CampoAuth,
    CampoContrasena,
} from '@/Components/Auth/CampoAuth';
import ReglasContrasena from '@/Components/ReglasContrasena';
import GuestLayout from '@/Layouts/GuestLayout';
import {
    contrasenaValida,
    emailValido,
    MENSAJES,
    useValidacion,
} from '@/utils/validaciones';
import { Head, useForm } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { FormEventHandler, ReactNode } from 'react';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            token: token,
            email: email,
            password: '',
            password_confirmation: '',
        });

    // Validación en vivo (KAN-313).
    type Campo = 'email' | 'password' | 'password_confirmation';
    const { estado, error, tocar, todoValido } = useValidacion<Campo>(
        {
            email: {
                valor: data.email,
                valido: emailValido(data.email),
                mensaje: MENSAJES.email,
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
    const cambiar = (campo: Campo, valor: string) => {
        setData(campo, valor);
        if (errors[campo]) clearErrors(campo);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!todoValido()) return;

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Nueva contraseña" />

            <h1 className="font-display text-2xl font-extrabold tracking-tight">
                Elegí tu nueva contraseña
            </h1>
            <p className="mt-1 text-sm text-slate-500">
                Después vas a poder ingresar con ella.
            </p>

            <form onSubmit={submit} noValidate className="mt-6 space-y-4">
                <CampoAuth
                    id="email"
                    etiqueta="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="username"
                    icono={Mail}
                    estado={estado('email')}
                    error={error('email')}
                    onChange={(e) => cambiar('email', e.target.value)}
                    onBlur={() => tocar('email')}
                    required
                />

                <CampoContrasena
                    id="password"
                    etiqueta="Contraseña nueva"
                    name="password"
                    value={data.password}
                    autoComplete="new-password"
                    enfocar
                    estado={estado('password')}
                    error={error('password')}
                    onChange={(e) => cambiar('password', e.target.value)}
                    onBlur={() => tocar('password')}
                    ayuda={<ReglasContrasena contrasena={data.password} />}
                    required
                />

                <CampoContrasena
                    id="password_confirmation"
                    etiqueta="Repetí la contraseña"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    autoComplete="new-password"
                    estado={estado('password_confirmation')}
                    error={error('password_confirmation')}
                    onChange={(e) =>
                        cambiar('password_confirmation', e.target.value)
                    }
                    onBlur={() => tocar('password_confirmation')}
                    required
                />

                <div className="pt-2">
                    <BotonAuth disabled={processing}>
                        Guardar contraseña
                    </BotonAuth>
                </div>
            </form>
        </>
    );
}

ResetPassword.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
