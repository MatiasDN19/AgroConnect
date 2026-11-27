import { BotonAuth } from '@/Components/Auth/CampoAuth';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CircleCheck, MailCheck } from 'lucide-react';
import { FormEventHandler, ReactNode, useEffect, useState } from 'react';

// "Verificá tu email" (KAN-312). Aparece después de registrarse y cuando una
// cuenta sin verificar intenta publicar, solicitar, reseñar o denunciar
// (middleware "verified", KAN-299). Usa el mismo GuestLayout que el login.

// Para no reenviar el mail en loop: después de mandarlo hay que esperar.
const ESPERA_SEGUNDOS = 60;

export default function VerifyEmail({ status }: { status?: string }) {
    const { auth } = usePage<PageProps>().props;
    const { post, processing } = useForm({});
    const [espera, setEspera] = useState(0);

    // Cuenta regresiva de a un segundo hasta llegar a 0.
    useEffect(() => {
        if (espera <= 0) return;
        const t = setTimeout(() => setEspera((s) => s - 1), 1000);
        return () => clearTimeout(t);
    }, [espera]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'), {
            onSuccess: () => setEspera(ESPERA_SEGUNDOS),
        });
    };

    return (
        <>
            <Head title="Verificá tu email" />

            <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-marca-azul ring-1 ring-sky-100">
                <MailCheck className="h-7 w-7" aria-hidden="true" />
            </span>

            <h1 className="mt-4 font-display text-2xl font-extrabold tracking-tight">
                Verificá tu email
            </h1>
            <p className="mt-1 text-sm text-slate-500">
                Te mandamos un enlace a{' '}
                <strong className="font-semibold text-marca-tinta">
                    {auth.user?.email}
                </strong>
                . Abrilo para confirmar que es tuyo y vas a poder publicar,
                solicitar compras y dejar reseñas.
            </p>
            <p className="mt-2 text-xs text-slate-400">
                Si no lo ves, fijate en la carpeta de spam o promociones.
            </p>

            {status === 'verification-link-sent' && (
                <div
                    role="status"
                    className="mt-4 flex gap-2 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-800"
                >
                    <CircleCheck
                        className="mt-0.5 h-4 w-4 shrink-0"
                        aria-hidden="true"
                    />
                    Te mandamos un enlace nuevo. Puede tardar un par de minutos
                    en llegar.
                </div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-3">
                <BotonAuth disabled={processing || espera > 0}>
                    {espera > 0
                        ? `Podés reenviarlo en ${espera} s`
                        : 'Reenviar el mail'}
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
        </>
    );
}

// Layout persistente, como el login y el registro: no se desmonta al pasar
// del registro a esta pantalla.
VerifyEmail.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
