import { clasesBotonIcono } from '@/Components/BotonIcono';
import PanelAlertas from '@/Components/PanelAlertas';
import { PageProps } from '@/types';
import { actualizarContador } from '@/utils/alertas';
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/react';
import { usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

// Cada cuánto se consulta si llegaron alertas nuevas mientras el usuario está quieto.
const INTERVALO_CONSULTA = 60_000;

// Campanita del navbar con la cantidad de alertas sin leer (KAN-74) y el panel
// desplegable con las últimas notificaciones (KAN-80).
// El número llega en todas las páginas como prop compartida (KAN-248).
export default function Campanita() {
    const { alertas_no_leidas: noLeidas = 0 } = usePage<PageProps>().props;

    // Cada tanto pedimos solo el número, si la pestaña está a la vista.
    // Al volver a la pestaña, consultamos enseguida.
    useEffect(() => {
        const consultar = () => {
            if (!document.hidden) actualizarContador();
        };

        const intervalo = setInterval(consultar, INTERVALO_CONSULTA);
        document.addEventListener('visibilitychange', consultar);

        return () => {
            clearInterval(intervalo);
            document.removeEventListener('visibilitychange', consultar);
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
        <Popover className="relative">
            {({ open, close }) => (
                <>
                    <PopoverButton
                        aria-label={etiqueta}
                        title={etiqueta}
                        className={clasesBotonIcono(open)}
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
                    </PopoverButton>

                    {/* anchor: Headless UI lo ubica debajo de la campana y lo
                        mantiene dentro de la pantalla (en el celu ocupa casi
                        todo el ancho). Misma animación que el menú de cuenta. */}
                    <PopoverPanel
                        anchor={{ to: 'bottom end', gap: 8, padding: 12 }}
                        transition
                        className="z-50 w-[min(24rem,calc(100vw-1.5rem))] origin-top-right rounded-2xl bg-white shadow-xl shadow-slate-900/10 ring-1 ring-slate-200 transition-[opacity,transform] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] focus:outline-none data-[closed]:-translate-y-1 data-[closed]:scale-95 data-[closed]:opacity-0 data-[leave]:duration-100 motion-reduce:data-[closed]:translate-y-0 motion-reduce:data-[closed]:scale-100"
                    >
                        <PanelAlertas noLeidas={noLeidas} cerrar={close} />
                    </PopoverPanel>
                </>
            )}
        </Popover>
    );
}
