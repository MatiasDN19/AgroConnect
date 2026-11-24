import { BotonAuth } from '@/Components/Auth/CampoAuth';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verificar correo" />

            <h1 className="font-display text-2xl font-extrabold tracking-tight">
                ¡Gracias por registrarte!
            </h1>
            <p className="mt-1 text-sm text-slate-500">
                Antes de empezar, confirmá tu correo con el enlace que te
                acabamos de mandar. Si no te llegó, te mandamos otro.
            </p>

            {status === 'verification-link-sent' && (
                <div
                    role="status"
                    className="mt-4 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-800"
                >
                    Te mandamos un enlace nuevo al correo con el que te
                    registraste.
                </div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-4">
                <BotonAuth disabled={processing}>
                    Reenviar el correo de verificación
                </BotonAuth>

                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="w-full rounded-xl py-2 text-center text-sm font-medium text-slate-500 hover:text-marca-tinta hover:underline"
                >
                    Cerrar sesión
                </Link>
            </form>
        </GuestLayout>
    );
}
