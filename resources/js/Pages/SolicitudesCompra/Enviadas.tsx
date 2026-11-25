import Paginacion from '@/Components/Paginacion';
import SocioBadge from '@/Components/SocioBadge';
import { EstadoSolicitud } from '@/Components/SolicitarCompra';
import WhatsappButton from '@/Components/WhatsappButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Paginado } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Clock, Inbox, Package } from 'lucide-react';

interface SolicitudEnviada {
    id: number;
    estado: EstadoSolicitud;
    cantidad: number;
    mensaje: string | null;
    created_at: string;
    updated_at: string;
    publicacion: {
        id: number;
        titulo: string;
        unidad: string;
        portada: string | null;
        fotos?: { url: string }[];
    } | null;
    productor: {
        id: number;
        name: string;
        socio_validado?: boolean;
        telefono?: string | null;
    } | null;
}

const BADGES: Record<EstadoSolicitud, { etiqueta: string; estilo: string }> = {
    pendiente: {
        etiqueta: 'Pendiente',
        estilo: 'bg-amber-100 text-amber-800 border-amber-200',
    },
    aceptada: {
        etiqueta: 'Aceptada',
        estilo: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    },
    rechazada: {
        etiqueta: 'Rechazada',
        estilo: 'bg-rose-100 text-rose-800 border-rose-200',
    },
};

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

export default function Enviadas({
    solicitudes,
}: PageProps<{ solicitudes: Paginado<SolicitudEnviada> }>) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-title text-2xl font-bold tracking-tight text-slate-900">
                            Mis solicitudes de compra
                        </h2>
                        <p className="text-sm text-slate-600">
                            Seguí el estado de las solicitudes que enviaste a
                            los productores.
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Mis solicitudes de compra" />

            <div className="mx-auto max-w-5xl py-8">
                {solicitudes.data.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                        <Inbox className="mx-auto h-12 w-12 text-slate-400" />
                        <h3 className="mt-4 text-base font-semibold text-slate-900">
                            No enviaste ninguna solicitud todavía
                        </h3>
                        <p className="mt-1 text-sm text-slate-500">
                            Explorá las publicaciones del catálogo y contactá a
                            los productores locales.
                        </p>
                        <div className="mt-6">
                            <Link
                                href={route('publicaciones.index')}
                                className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                            >
                                <Package className="h-4 w-4" />
                                Ver publicaciones
                            </Link>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {solicitudes.data.map((solicitud) => {
                            const badge = BADGES[solicitud.estado];
                            const portada =
                                solicitud.publicacion?.portada ||
                                solicitud.publicacion?.fotos?.[0]?.url;

                            return (
                                <div
                                    key={solicitud.id}
                                    className="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300"
                                >
                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="flex gap-4">
                                            {portada ? (
                                                <img
                                                    src={portada}
                                                    alt={
                                                        solicitud.publicacion
                                                            ?.titulo || ''
                                                    }
                                                    className="h-20 w-20 flex-shrink-0 rounded-xl object-cover"
                                                />
                                            ) : (
                                                <div className="flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                                    <Package className="h-8 w-8" />
                                                </div>
                                            )}

                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <span
                                                        className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold ${badge.estilo}`}
                                                    >
                                                        {badge.etiqueta}
                                                    </span>
                                                    <span className="flex items-center gap-1 text-xs text-slate-400">
                                                        <Clock className="h-3 w-3" />
                                                        {formatoFecha.format(
                                                            new Date(
                                                                solicitud.created_at,
                                                            ),
                                                        )}
                                                    </span>
                                                </div>

                                                <h3 className="text-base font-bold text-slate-900">
                                                    {solicitud.publicacion ? (
                                                        <Link
                                                            href={route(
                                                                'publicaciones.show',
                                                                solicitud
                                                                    .publicacion
                                                                    .id,
                                                            )}
                                                            className="hover:text-emerald-700 hover:underline"
                                                        >
                                                            {
                                                                solicitud
                                                                    .publicacion
                                                                    .titulo
                                                            }
                                                        </Link>
                                                    ) : (
                                                        <span className="italic text-slate-500">
                                                            Publicación no
                                                            disponible
                                                        </span>
                                                    )}
                                                </h3>

                                                {solicitud.productor && (
                                                    <div className="flex items-center gap-2 text-sm text-slate-600">
                                                        <span>
                                                            Productor:{' '}
                                                            <strong>
                                                                {
                                                                    solicitud
                                                                        .productor
                                                                        .name
                                                                }
                                                            </strong>
                                                        </span>
                                                        {solicitud.productor
                                                            .socio_validado && (
                                                            <SocioBadge />
                                                        )}
                                                    </div>
                                                )}

                                                <div className="text-sm text-slate-700">
                                                    Cantidad solicitada:{' '}
                                                    <span className="font-semibold">
                                                        {solicitud.cantidad}
                                                    </span>{' '}
                                                    {solicitud.publicacion
                                                        ?.unidad || 'unidades'}
                                                </div>

                                                {solicitud.mensaje && (
                                                    <p className="mt-2 rounded-lg border border-slate-100 bg-slate-50 p-2.5 text-sm text-slate-600">
                                                        <span className="font-medium text-slate-700">
                                                            Tu mensaje:{' '}
                                                        </span>
                                                        {solicitud.mensaje}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        {solicitud.estado === 'aceptada' &&
                                            solicitud.productor?.telefono && (
                                                <div className="mt-3 flex-shrink-0 sm:mt-0">
                                                    <WhatsappButton
                                                        telefono={
                                                            solicitud.productor
                                                                .telefono
                                                        }
                                                        mensaje={`Hola ${solicitud.productor.name}, te escribo por la solicitud de compra aceptada de ${solicitud.cantidad} ${solicitud.publicacion?.unidad || 'unidades'} de "${solicitud.publicacion?.titulo || 'tu publicación'}" en AgroConnect.`}
                                                    />
                                                </div>
                                            )}
                                    </div>
                                </div>
                            );
                        })}

                        {solicitudes.last_page > 1 && (
                            <div className="mt-6">
                                <Paginacion paginado={solicitudes} />
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
