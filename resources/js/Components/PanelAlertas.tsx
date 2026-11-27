import { abrirAlerta, Alerta } from '@/utils/alertas';
import { haceCuanto } from '@/utils/fecha';
import { Link } from '@inertiajs/react';
import {
    Bell,
    CircleCheck,
    CircleX,
    Flag,
    LucideIcon,
    ShoppingBag,
    Star,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

// Panel desplegable de la campanita con las últimas notificaciones (KAN-80).
// Se monta recién al abrir el panel, así que cada vez que se abre pide la
// lista actualizada.

// Cuántas se muestran en el panel; el resto está en "Ver todas".
const CANTIDAD_EN_PANEL = 8;

// Ícono y color según el tipo. Los tipos salen de los controladores que crean
// alertas (solicitudes, reseñas y denuncias).
const ESTILO_TIPO: Record<string, { Icono: LucideIcon; color: string }> = {
    nueva_solicitud_compra: {
        Icono: ShoppingBag,
        color: 'bg-sky-50 text-marca-azul',
    },
    solicitud_compra_aceptada: {
        Icono: CircleCheck,
        color: 'bg-green-50 text-green-600',
    },
    solicitud_compra_rechazada: {
        Icono: CircleX,
        color: 'bg-red-50 text-red-600',
    },
    nueva_resena: { Icono: Star, color: 'bg-amber-50 text-amber-600' },
    denuncia_resuelta: { Icono: Flag, color: 'bg-slate-100 text-slate-600' },
};
const ESTILO_GENERICO = { Icono: Bell, color: 'bg-sky-50 text-marca-azul' };

// El destino de cada alerta y cómo se abre están en utils/alertas (KAN-344),
// compartidos con la página /alertas.

export default function PanelAlertas({
    noLeidas,
    cerrar,
}: {
    noLeidas: number;
    cerrar: () => void;
}) {
    const [alertas, setAlertas] = useState<Alerta[] | null>(null);
    const [fallo, setFallo] = useState(false);

    const cargar = useCallback((senal?: AbortSignal) => {
        setFallo(false);
        fetch(route('alertas.index'), {
            headers: { Accept: 'application/json' },
            signal: senal,
        })
            .then((r) => (r.ok ? r.json() : Promise.reject()))
            .then((datos: Alerta[]) => setAlertas(datos))
            .catch((e) => {
                if (e?.name !== 'AbortError') setFallo(true);
            });
    }, []);

    useEffect(() => {
        const controlador = new AbortController();
        cargar(controlador.signal);
        return () => controlador.abort();
    }, [cargar]);

    const abrir = (alerta: Alerta) =>
        abrirAlerta(alerta, {
            alMarcarLeida: () =>
                setAlertas(
                    (lista) =>
                        lista?.map((a) =>
                            a.id === alerta.id ? { ...a, leida: true } : a,
                        ) ?? null,
                ),
            antesDeNavegar: cerrar,
        });

    const visibles = alertas?.slice(0, CANTIDAD_EN_PANEL) ?? [];

    return (
        <div className="flex max-h-[min(32rem,calc(100dvh-6rem))] flex-col">
            <div className="flex items-baseline justify-between gap-2 border-b border-slate-100 px-4 py-3">
                <h2 className="text-sm font-semibold text-marca-tinta">
                    Notificaciones
                </h2>
                {noLeidas > 0 && (
                    <span className="text-xs font-medium text-marca-azul">
                        {noLeidas} sin leer
                    </span>
                )}
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-1.5">
                {fallo ? (
                    <div className="px-4 py-8 text-center text-sm text-slate-500">
                        No pudimos cargar las notificaciones.{' '}
                        <button
                            type="button"
                            onClick={() => cargar()}
                            className="font-medium text-marca-azul underline-offset-2 hover:underline"
                        >
                            Reintentar
                        </button>
                    </div>
                ) : alertas === null ? (
                    <Esqueleto />
                ) : visibles.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 px-4 py-10 text-center">
                        <span className="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <Bell className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <p className="text-sm font-medium text-slate-700">
                            No tenés notificaciones
                        </p>
                        <p className="text-xs text-slate-500">
                            Te avisamos acá cuando respondan tus solicitudes o
                            reseñen tus publicaciones.
                        </p>
                    </div>
                ) : (
                    <ul className="space-y-0.5">
                        {visibles.map((alerta) => {
                            const { Icono, color } =
                                ESTILO_TIPO[alerta.tipo] ?? ESTILO_GENERICO;

                            return (
                                <li key={alerta.id}>
                                    <button
                                        type="button"
                                        onClick={() => abrir(alerta)}
                                        className={`flex w-full items-start gap-3 rounded-xl px-2.5 py-2.5 text-left transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                                            alerta.leida ? '' : 'bg-sky-50/60'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${color}`}
                                        >
                                            <Icono
                                                className="h-[1.125rem] w-[1.125rem]"
                                                aria-hidden="true"
                                            />
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span
                                                className={`block text-sm leading-snug ${
                                                    alerta.leida
                                                        ? 'text-slate-600'
                                                        : 'font-medium text-marca-tinta'
                                                }`}
                                            >
                                                {alerta.mensaje}
                                            </span>
                                            <span className="mt-0.5 block text-xs text-slate-400">
                                                {haceCuanto(alerta.created_at)}
                                            </span>
                                        </span>
                                        {!alerta.leida && (
                                            <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-marca-azul">
                                                <span className="sr-only">
                                                    Sin leer
                                                </span>
                                            </span>
                                        )}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>

            <div className="border-t border-slate-100 p-1.5">
                <Link
                    href={route('alertas.index')}
                    onClick={cerrar}
                    className="block rounded-xl px-3 py-2 text-center text-sm font-medium text-marca-azul transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                >
                    Ver todas
                </Link>
            </div>
        </div>
    );
}

// Tres filas grises mientras carga, con la misma forma que las reales.
function Esqueleto() {
    return (
        <ul aria-hidden="true" className="space-y-0.5">
            {[0, 1, 2].map((i) => (
                <li
                    key={i}
                    className="flex animate-pulse items-start gap-3 px-2.5 py-2.5"
                >
                    <span className="h-9 w-9 shrink-0 rounded-full bg-slate-100" />
                    <span className="flex-1 space-y-2 pt-1">
                        <span className="block h-3 w-11/12 rounded bg-slate-100" />
                        <span className="block h-3 w-1/3 rounded bg-slate-100" />
                    </span>
                </li>
            ))}
        </ul>
    );
}
