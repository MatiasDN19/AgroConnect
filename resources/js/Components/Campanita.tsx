import BotonIcono from '@/Components/BotonIcono';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

// Cada cuánto se consulta si llegaron alertas nuevas mientras el usuario está quieto.
const INTERVALO_CONSULTA = 60_000;

// Campanita del navbar con la cantidad de alertas sin leer (KAN-74).
// El número llega en todas las páginas como prop compartida (KAN-248).
// El panel desplegable con el listado es KAN-80; por ahora lleva a /alertas.
export default function Campanita() {
    const { alertas_no_leidas: noLeidas = 0 } = usePage<PageProps>().props;

    // Cada tanto pedimos solo el número (y el flash, ver abajo), si la pestaña
    // está a la vista. Al volver a la pestaña, consultamos enseguida.
    useEffect(() => {
        const consultar = () => {
            if (document.hidden) return;
            router.reload({
                // "flash" también, para que llegue fresco: si no, Inertia
                // conserva el de la página y el aviso se repetiría.
                only: ['alertas_no_leidas', 'flash'],
                // async: no cancela una navegación que el usuario esté haciendo.
                async: true,
            });
        };

        const intervalo = setInterval(consultar, INTERVALO_CONSULTA);
        const alCambiarPestana = () => {
            if (!document.hidden) consultar();
        };
        document.addEventListener('visibilitychange', alCambiarPestana);

        return () => {
            clearInterval(intervalo);
            document.removeEventListener('visibilitychange', alCambiarPestana);
        };
    }, []);

    // El globito hace un "pop" solo cuando el número sube (llegó algo nuevo),
    // no cada vez que se cambia de página ni cuando baja.
    const anterior = useRef(noLeidas);
    const [pop, setPop] = useState(0);
    useEffect(() => {
        if (noLeidas > anterior.current) setPop((n) => n + 1);
        anterior.current = noLeidas;
    }, [noLeidas]);

    const etiqueta =
        noLeidas > 0
            ? `Notificaciones, ${noLeidas} sin leer`
            : 'Notificaciones';

    return (
        <BotonIcono
            href={route('alertas.index')}
            etiqueta={etiqueta}
            activo={route().current('alertas.index')}
        >
            <Bell className="h-5 w-5" aria-hidden="true" />
            {noLeidas > 0 && (
                <span
                    // Cambiar la key reinicia la animación.
                    key={pop}
                    aria-hidden="true"
                    className={`absolute right-0.5 top-0.5 flex h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white ${
                        pop > 0 ? 'globito-pop' : ''
                    }`}
                >
                    {noLeidas > 9 ? '9+' : noLeidas}
                </span>
            )}
        </BotonIcono>
    );
}
