import { avisar } from '@/Components/Avisos/avisos';
import ItemAlerta from '@/Components/ItemAlerta';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import {
    abrirAlerta,
    actualizarContador,
    Alerta,
    marcarTodasLeidas,
} from '@/utils/alertas';
import { Head, Link } from '@inertiajs/react';
import { Bell, CheckCheck, PartyPopper } from 'lucide-react';
import { ReactNode, useEffect, useMemo, useState } from 'react';

// Página de notificaciones (KAN-323). Usa el mismo renglón que el panel de la
// campanita (ItemAlerta) y el mismo destino al tocarlas (utils/alertas, KAN-344).
// El backend manda todas las alertas: el filtro "No leídas" y los grupos por
// día se arman acá.

type Filtro = 'todas' | 'no-leidas';

const GRUPOS = ['Hoy', 'Ayer', 'Esta semana', 'Antes'] as const;
type Grupo = (typeof GRUPOS)[number];

// En qué grupo cae cada fecha, contando días de calendario (no 24 h).
function grupoDe(fecha: string): Grupo {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const dia = new Date(fecha);
    dia.setHours(0, 0, 0, 0);
    const dias = Math.round((hoy.getTime() - dia.getTime()) / 86_400_000);
    if (dias <= 0) return 'Hoy';
    if (dias === 1) return 'Ayer';
    if (dias < 7) return 'Esta semana';
    return 'Antes';
}

export default function Index({
    alertas: alertasIniciales,
}: PageProps<{ alertas: Alerta[] }>) {
    // Copia local: al tocar una o marcar todas se actualiza al momento.
    const [alertas, setAlertas] = useState(alertasIniciales);
    useEffect(() => setAlertas(alertasIniciales), [alertasIniciales]);

    const [filtro, setFiltro] = useState<Filtro>('todas');
    const [marcando, setMarcando] = useState(false);

    const noLeidas = alertas.filter((a) => !a.leida);
    const visibles = filtro === 'todas' ? alertas : noLeidas;

    const grupos = useMemo(() => {
        const mapa = new Map<Grupo, Alerta[]>();
        for (const alerta of visibles) {
            const g = grupoDe(alerta.created_at);
            mapa.set(g, [...(mapa.get(g) ?? []), alerta]);
        }
        return GRUPOS.filter((g) => mapa.has(g)).map((g) => ({
            titulo: g,
            items: mapa.get(g)!,
        }));
    }, [visibles]);

    const marcarLocal = (ids: number[]) =>
        setAlertas((lista) =>
            lista.map((a) => (ids.includes(a.id) ? { ...a, leida: true } : a)),
        );

    const abrir = (alerta: Alerta) =>
        abrirAlerta(alerta, { alMarcarLeida: () => marcarLocal([alerta.id]) });

    const marcarTodas = async () => {
        const ids = noLeidas.map((a) => a.id);
        if (ids.length === 0) return;
        setMarcando(true);
        marcarLocal(ids);
        await marcarTodasLeidas(ids);
        actualizarContador();
        setMarcando(false);
        avisar(
            'exito',
            ids.length === 1
                ? 'Marcamos 1 notificación como leída.'
                : `Marcamos ${ids.length} notificaciones como leídas.`,
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Notificaciones" />

            <div className="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="font-display text-3xl font-bold text-marca-tinta">
                            Notificaciones
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {noLeidas.length === 0
                                ? 'Estás al día.'
                                : noLeidas.length === 1
                                  ? 'Tenés 1 sin leer.'
                                  : `Tenés ${noLeidas.length} sin leer.`}
                        </p>
                    </div>

                    {noLeidas.length > 0 && (
                        <button
                            type="button"
                            onClick={marcarTodas}
                            disabled={marcando}
                            className="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul active:scale-[0.98] disabled:cursor-wait disabled:opacity-60"
                        >
                            <CheckCheck
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                            Marcar todas como leídas
                        </button>
                    )}
                </header>

                {alertas.length > 0 && (
                    <div
                        role="tablist"
                        aria-label="Filtrar notificaciones"
                        className="inline-flex rounded-full bg-white p-1 shadow-sm ring-1 ring-slate-200"
                    >
                        {(
                            [
                                ['todas', 'Todas', alertas.length],
                                ['no-leidas', 'No leídas', noLeidas.length],
                            ] as const
                        ).map(([valor, texto, cantidad]) => {
                            const activo = filtro === valor;
                            return (
                                <button
                                    key={valor}
                                    type="button"
                                    role="tab"
                                    aria-selected={activo}
                                    onClick={() => setFiltro(valor)}
                                    className={`inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                                        activo
                                            ? 'bg-marca-azul-profundo text-white'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    }`}
                                >
                                    {texto}
                                    <span
                                        className={`rounded-full px-1.5 text-xs tabular-nums ${
                                            activo
                                                ? 'bg-white/20'
                                                : 'bg-slate-100 text-slate-500'
                                        }`}
                                    >
                                        {cantidad}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                )}

                {alertas.length === 0 ? (
                    <Vacio
                        Icono={Bell}
                        titulo="No tenés notificaciones"
                        texto="Te avisamos acá cuando te manden o respondan una solicitud de compra, cuando reseñen tus publicaciones o cuando se resuelva una denuncia."
                    />
                ) : visibles.length === 0 ? (
                    <Vacio
                        Icono={PartyPopper}
                        titulo="Leíste todo"
                        texto="No tenés notificaciones sin leer."
                        accion={
                            <button
                                type="button"
                                onClick={() => setFiltro('todas')}
                                className="text-sm font-medium text-marca-azul underline-offset-2 hover:underline"
                            >
                                Ver todas
                            </button>
                        }
                    />
                ) : (
                    <div className="space-y-6">
                        {grupos.map(({ titulo, items }) => (
                            <section key={titulo} aria-label={titulo}>
                                <h2 className="mb-2 px-1 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    {titulo}
                                </h2>
                                <ul className="space-y-1 rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-200">
                                    {items.map((alerta) => (
                                        <li key={alerta.id}>
                                            <ItemAlerta
                                                alerta={alerta}
                                                onClick={() => abrir(alerta)}
                                                grande
                                            />
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

// Estado vacío "ilustrado": el ícono en círculos concéntricos suaves.
function Vacio({
    Icono,
    titulo,
    texto,
    accion,
}: {
    Icono: typeof Bell;
    titulo: string;
    texto: string;
    accion?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200">
            <span className="flex h-24 w-24 items-center justify-center rounded-full bg-sky-50">
                <span className="flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 text-marca-azul">
                    <Icono className="h-7 w-7" aria-hidden="true" />
                </span>
            </span>
            <p className="mt-1 font-display text-lg font-bold text-marca-tinta">
                {titulo}
            </p>
            <p className="max-w-sm text-sm text-slate-500">{texto}</p>
            {accion}
            {!accion && (
                <Link
                    href={route('publicaciones.index')}
                    className="text-sm font-medium text-marca-azul underline-offset-2 hover:underline"
                >
                    Ir al marketplace →
                </Link>
            )}
        </div>
    );
}
