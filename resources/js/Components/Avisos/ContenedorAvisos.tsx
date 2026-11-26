import { PageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useSyncExternalStore } from 'react';
import AvisoFlotante from './AvisoFlotante';
import { avisar, obtenerAvisos, suscribir } from './avisos';

type Flash = PageProps['flash'] | undefined;

function mostrarFlash(flash: Flash) {
    if (flash?.success) avisar('exito', flash.success);
    if (flash?.error) avisar('error', flash.error);
}

// Se monta una sola vez en app.tsx, por fuera de los layouts, así un aviso
// no se corta al pasar de una pantalla con un layout a otra con otro
// (por ejemplo, del login al Inicio).
export default function ContenedorAvisos({
    flashInicial,
}: {
    flashInicial: Flash;
}) {
    const avisos = useSyncExternalStore(suscribir, obtenerAvisos);

    // El flash de la primera carga (cuando se entra escribiendo la dirección o con F5).
    const yaMostroInicial = useRef(false);
    useEffect(() => {
        if (yaMostroInicial.current) return;
        yaMostroInicial.current = true;
        mostrarFlash(flashInicial);
    }, [flashInicial]);

    // Y el de cada navegación de Inertia. Escuchamos el evento y no el cambio de
    // props a propósito: si se guarda dos veces seguidas con el mismo mensaje,
    // el texto no cambia pero igual hay que avisar.
    useEffect(
        () =>
            router.on('success', (evento) =>
                mostrarFlash(
                    (evento.detail.page.props as Partial<PageProps>).flash,
                ),
            ),
        [],
    );

    return (
        <section
            aria-label="Avisos"
            className="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-stretch px-1 pb-1.5 sm:items-end sm:px-3 sm:pb-3"
        >
            {avisos.map((aviso) => (
                <AvisoFlotante key={aviso.id} aviso={aviso} />
            ))}
        </section>
    );
}
