import { BotonAuth, CampoAuth } from '@/Components/Auth/CampoAuth';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { FormEventHandler, ReactNode } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <>
            <Head title="Recuperar contraseña" />

            <h1 className="font-display text-2xl font-extrabold tracking-tight">
                ¿Olvidaste tu contraseña?
            </h1>
            <p className="mt-1 text-sm text-slate-500">
                No pasa nada. Escribí tu correo y te mandamos un enlace para
                elegir una nueva.
            </p>

            {status && (
                <div
                    role="status"
                    className="mt-4 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-800"
                >
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-4">
                <CampoAuth
                    id="email"
                    etiqueta="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="username"
                    placeholder="tucorreo@ejemplo.com"
                    icono={Mail}
                    enfocar
                    error={errors.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                />

                <div className="pt-2">
                    <BotonAuth disabled={processing}>
                        Enviarme el enlace
                    </BotonAuth>
                </div>
            </form>

            <p className="mt-6 text-center text-sm text-slate-500">
                ¿Te acordaste?{' '}
                <Link
                    href={route('login')}
                    className="font-semibold text-marca-azul hover:underline"
                >
                    Volver a ingresar
                </Link>
            </p>
        </>
    );
}

ForgotPassword.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
