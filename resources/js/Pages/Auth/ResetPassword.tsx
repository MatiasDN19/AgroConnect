import {
    BotonAuth,
    CampoAuth,
    CampoContrasena,
} from '@/Components/Auth/CampoAuth';
import GuestLayout from '@/Layouts/GuestLayout';
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
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

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

            <form onSubmit={submit} className="mt-6 space-y-4">
                <CampoAuth
                    id="email"
                    etiqueta="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="username"
                    icono={Mail}
                    error={errors.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                />

                <CampoContrasena
                    id="password"
                    etiqueta="Contraseña nueva"
                    name="password"
                    value={data.password}
                    autoComplete="new-password"
                    enfocar
                    error={errors.password}
                    onChange={(e) => setData('password', e.target.value)}
                    required
                />

                <CampoContrasena
                    id="password_confirmation"
                    etiqueta="Repetí la contraseña"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    autoComplete="new-password"
                    error={errors.password_confirmation}
                    onChange={(e) =>
                        setData('password_confirmation', e.target.value)
                    }
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
