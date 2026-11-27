import { avisar } from '@/Components/Avisos/avisos';
import { PageProps } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { MailWarning, X } from 'lucide-react';
import { useEffect, useState } from 'react';

// Avisos para cuentas sin verificar (KAN-312). El backend (KAN-299) bloquea
// publicar, solicitar, reseñar y denunciar hasta verificar el email.

export function useEmailVerificado(): boolean {
    const { auth } = usePage<PageProps>().props;
    return Boolean(auth.user?.email_verified_at);
}

// Para el cierre de la franja "por esta visita": sessionStorage dura hasta que
// se cierra la pestaña. Puede fallar (modo privado, bloqueado): no pasa nada.
const CLAVE_CERRADA = 'aviso-verificar-email-cerrado';

function reenviar() {
    router.post(
        route('verification.send'),
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                avisar(
                    'exito',
                    'Te mandamos un enlace nuevo. Revisá tu correo.',
                ),
        },
    );
}

// Franja debajo del navbar.
export function FranjaVerificarEmail() {
    const verificado = useEmailVerificado();
    const [cerrada, setCerrada] = useState(true);

    useEffect(() => {
        try {
            setCerrada(sessionStorage.getItem(CLAVE_CERRADA) === '1');
        } catch {
            setCerrada(false);
        }

        // Después de abrir el enlace del mail, Laravel vuelve con ?verified=1:
        // se avisa y se saca el parámetro de la dirección.
        const url = new URL(window.location.href);
        if (url.searchParams.get('verified') === '1') {
            avisar(
                'exito',
                'Verificaste tu email. Ya podés publicar y solicitar compras.',
            );
            url.searchParams.delete('verified');
            // Con la cola de Inertia (visita del lado del cliente, sin pedir
            // nada al servidor): si se cambia a mano, Inertia la vuelve a pisar.
            router.replace({
                url: url.pathname + url.search + url.hash,
                preserveState: true,
                preserveScroll: true,
            });
        }
    }, []);

    if (verificado || cerrada) return null;

    const cerrar = () => {
        setCerrada(true);
        try {
            sessionStorage.setItem(CLAVE_CERRADA, '1');
        } catch {
            // Sin almacenamiento: se cierra igual, hasta recargar.
        }
    };

    return (
        <div
            role="status"
            className="border-b border-amber-200 bg-amber-50 text-amber-950"
        >
            <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2.5 text-sm sm:px-6 lg:px-8">
                <MailWarning
                    className="h-5 w-5 shrink-0 text-amber-600"
                    aria-hidden="true"
                />
                <p className="flex-1">
                    Verificá tu email para publicar y solicitar compras.{' '}
                    <button
                        type="button"
                        onClick={reenviar}
                        className="font-semibold underline underline-offset-2 hover:text-amber-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-600"
                    >
                        Reenviar el mail
                    </button>
                </p>
                <button
                    type="button"
                    onClick={cerrar}
                    aria-label="Cerrar el aviso"
                    className="shrink-0 rounded-md p-1 text-amber-700 transition-colors hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-600"
                >
                    <X className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>
    );
}

// En lugar de un formulario que el servidor después rechazaría (solicitar,
// reseñar, denunciar): avisa antes y lleva a verificar.
export function BloqueVerificarEmail({ accion }: { accion: string }) {
    return (
        <div className="flex gap-3 rounded-xl bg-amber-50 p-3.5 text-sm text-amber-950 ring-1 ring-amber-200">
            <MailWarning
                className="mt-0.5 h-5 w-5 shrink-0 text-amber-600"
                aria-hidden="true"
            />
            <div>
                <p>Para {accion} tenés que verificar tu email.</p>
                <Link
                    href={route('verification.notice')}
                    className="mt-1 inline-block font-semibold underline underline-offset-2 hover:text-amber-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-600"
                >
                    Verificar mi email
                </Link>
            </div>
        </div>
    );
}
