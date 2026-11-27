import BotonExportar from '@/Components/Admin/BotonExportar';
import Contador from '@/Components/Landing/Contador';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageProps } from '@/types';
import { saludo } from '@/utils/fecha';
import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    Flag,
    LucideIcon,
    MessageSquareText,
    Package,
    Users,
} from 'lucide-react';
import { CSSProperties, useState } from 'react';

// Resumen del panel admin (KAN-159), con el diseño "reel" acordado: saludo, una
// tarjeta destacada con lo que hay que atender (denuncias pendientes), tarjetas
// chicas con los demás números y las últimas denuncias a la derecha.
// Sin librerías: entrada escalonada con .aparecer (app.css) y números con Contador.

type Metricas = {
    total_usuarios: number;
    total_publicaciones_activas: number;
    total_denuncias_pendientes: number;
    total_resenas: number;
    total_socios_pendientes: number;
};

type DenunciaReciente = {
    id: number;
    motivo: string;
    created_at: string;
    publicacion: { id: number; titulo: string } | null;
};

const orden = (n: number) => ({ '--orden': n }) as CSSProperties;

const formatoFechaLarga = new Intl.DateTimeFormat('es-AR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

const relativo = new Intl.RelativeTimeFormat('es-AR', { numeric: 'auto' });

// "hace 5 minutos", "hace 2 horas", "ayer"...
function haceCuanto(fecha: string): string {
    const segundos = (new Date(fecha).getTime() - Date.now()) / 1000;
    const pasos: [Intl.RelativeTimeFormatUnit, number][] = [
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    for (const [unidad, enSegundos] of pasos) {
        if (Math.abs(segundos) >= enSegundos) {
            return relativo.format(Math.round(segundos / enSegundos), unidad);
        }
    }
    return 'recién';
}

function fechaDeHoy(): string {
    const texto = formatoFechaLarga.format(new Date());
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

export default function Index({
    metricas,
    ultimas_denuncias,
}: {
    metricas: Metricas;
    ultimas_denuncias: DenunciaReciente[];
}) {
    const { auth } = usePage<PageProps>().props;
    const nombre = auth.user.name.split(' ')[0];
    const pendientes = metricas.total_denuncias_pendientes;

    return (
        <AdminLayout titulo="Resumen">
            <div className="mx-auto max-w-7xl space-y-6">
                <div className="aparecer" style={orden(0)}>
                    <p className="text-sm text-slate-500">
                        {/* Solo la primera letra en mayúscula ("Martes, 24 de noviembre"). */}
                        {fechaDeHoy()}
                    </p>
                    <h2 className="font-display text-2xl font-extrabold tracking-tight text-marca-tinta sm:text-3xl">
                        {saludo()}, {nombre} 👋
                    </h2>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {/* Tarjeta destacada: lo que hay que atender */}
                        <section
                            className="aparecer relative overflow-hidden rounded-2xl bg-gradient-to-br from-marca-ambar via-amber-400 to-orange-400 p-6 text-amber-950 shadow-lg shadow-amber-900/10 sm:p-8"
                            style={orden(1)}
                        >
                            <Flag
                                className="absolute -right-6 -top-6 h-40 w-40 rotate-12 text-white/20"
                                aria-hidden="true"
                            />
                            <p className="text-sm font-semibold uppercase tracking-wider text-amber-900/80">
                                Denuncias pendientes
                            </p>
                            <p className="mt-2 font-display text-6xl font-extrabold leading-none tracking-tight sm:text-7xl">
                                <Contador valor={pendientes} />
                            </p>
                            <p className="mt-3 max-w-md text-sm text-amber-950/80">
                                {pendientes === 0
                                    ? '¡Todo en orden! No hay denuncias esperando revisión.'
                                    : pendientes === 1
                                      ? 'Hay una denuncia esperando que la revises.'
                                      : `Hay ${pendientes} denuncias esperando que las revises.`}
                            </p>
                            {pendientes > 0 && (
                                <Link
                                    href={route('admin.denuncias.index')}
                                    className="mt-5 inline-flex items-center gap-2 rounded-xl bg-amber-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                >
                                    Revisar denuncias
                                    <ArrowRight
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                </Link>
                            )}
                        </section>

                        {/* Tarjetas chicas con los demás números */}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <TarjetaNumero
                                orden={2}
                                Icono={Users}
                                etiqueta="Usuarios registrados"
                                valor={metricas.total_usuarios}
                            />
                            <TarjetaNumero
                                orden={3}
                                Icono={Package}
                                etiqueta="Publicaciones activas"
                                valor={metricas.total_publicaciones_activas}
                                href={route('admin.publicaciones.index', {
                                    estado: 'activa',
                                })}
                            />
                            <TarjetaNumero
                                orden={4}
                                Icono={MessageSquareText}
                                etiqueta="Reseñas"
                                valor={metricas.total_resenas}
                            />
                            <TarjetaNumero
                                orden={5}
                                Icono={BadgeCheck}
                                etiqueta="Solicitudes de socio"
                                valor={metricas.total_socios_pendientes}
                                href={route('admin.socios.index')}
                                destacar={metricas.total_socios_pendientes > 0}
                            />
                        </div>
                    </div>

                    {/* Últimas denuncias pendientes */}
                    <section
                        className="aparecer flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
                        style={orden(3)}
                        aria-labelledby="titulo-ultimas"
                    >
                        <h2
                            id="titulo-ultimas"
                            className="font-display text-lg font-bold text-marca-tinta"
                        >
                            Últimas denuncias
                        </h2>

                        {ultimas_denuncias.length === 0 ? (
                            <p className="mt-4 flex-1 text-sm text-slate-500">
                                No hay denuncias pendientes. 🎉
                            </p>
                        ) : (
                            <ul className="mt-4 flex-1 divide-y divide-slate-100">
                                {ultimas_denuncias.map((denuncia) => (
                                    <li key={denuncia.id} className="py-3">
                                        <p className="text-sm font-medium text-slate-900">
                                            {denuncia.motivo}
                                        </p>
                                        <p className="mt-0.5 truncate text-xs text-slate-500">
                                            {denuncia.publicacion
                                                ? denuncia.publicacion.titulo
                                                : 'Publicación eliminada'}{' '}
                                            · {haceCuanto(denuncia.created_at)}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <Link
                            href={route('admin.denuncias.index')}
                            className="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-marca-azul hover:underline"
                        >
                            Ver todas las denuncias
                            <ArrowRight
                                className="h-4 w-4"
                                aria-hidden="true"
                            />
                        </Link>
                    </section>
                </div>

                <ExportarSolicitudes />
            </div>
        </AdminLayout>
    );
}

// Exportar las solicitudes de compra (KAN-318). No hay una pantalla de
// solicitudes en el panel, así que va acá, con el filtro por estado.
const ESTADOS_SOLICITUD = [
    { valor: '', etiqueta: 'Todas' },
    { valor: 'pendiente', etiqueta: 'Pendientes' },
    { valor: 'aceptada', etiqueta: 'Aceptadas' },
    { valor: 'rechazada', etiqueta: 'Rechazadas' },
];

function ExportarSolicitudes() {
    const [estado, setEstado] = useState('');

    return (
        <section
            className="aparecer flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:flex-row sm:items-center sm:justify-between"
            style={orden(4)}
            aria-labelledby="titulo-exportar"
        >
            <div>
                <h2
                    id="titulo-exportar"
                    className="font-display text-lg font-bold text-marca-tinta"
                >
                    Exportar solicitudes de compra
                </h2>
                <p className="text-sm text-slate-500">
                    Bajá una planilla (CSV) para abrirla en Excel o Google
                    Sheets.
                </p>
            </div>
            <div className="flex flex-wrap items-center gap-3">
                <label htmlFor="estado-exportar" className="sr-only">
                    Estado de las solicitudes
                </label>
                <select
                    id="estado-exportar"
                    value={estado}
                    onChange={(e) => setEstado(e.target.value)}
                    className="rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600"
                >
                    {ESTADOS_SOLICITUD.map(({ valor, etiqueta }) => (
                        <option key={valor} value={valor}>
                            {etiqueta}
                        </option>
                    ))}
                </select>
                <BotonExportar
                    href={route(
                        'admin.exportar.solicitudes',
                        estado ? { estado } : {},
                    )}
                />
            </div>
        </section>
    );
}

function TarjetaNumero({
    orden: n,
    Icono,
    etiqueta,
    valor,
    href,
    destacar = false,
}: {
    orden: number;
    Icono: LucideIcon;
    etiqueta: string;
    valor: number;
    href?: string;
    destacar?: boolean;
}) {
    const contenido = (
        <>
            <span
                className={`flex h-10 w-10 items-center justify-center rounded-xl ${
                    destacar
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-sky-50 text-marca-azul'
                }`}
            >
                <Icono className="h-5 w-5" aria-hidden="true" />
            </span>
            <span>
                <span className="block font-display text-3xl font-extrabold tracking-tight text-marca-tinta">
                    <Contador valor={valor} />
                </span>
                <span className="block text-sm text-slate-500">{etiqueta}</span>
            </span>
        </>
    );

    const clases =
        'aparecer flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200';

    return href ? (
        <Link
            href={href}
            className={`${clases} transition hover:shadow-md hover:ring-sky-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600`}
            style={orden(n)}
        >
            {contenido}
        </Link>
    ) : (
        <div className={clases} style={orden(n)}>
            {contenido}
        </div>
    );
}
