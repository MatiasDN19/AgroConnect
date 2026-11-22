import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { EstadoSolicitud } from '@/Components/SolicitarCompra';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { formatearPrecio } from '@/utils/formato';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Inbox, Mail, Phone } from 'lucide-react';
import { useState } from 'react';

// Lo que manda SolicitudCompraController::index(). El contacto del comprador
// (email y teléfono) solo viene cuando la solicitud está aceptada.
interface Solicitud {
    id: number;
    estado: EstadoSolicitud;
    mensaje: string | null;
    created_at: string;
    updated_at: string;
    publicacion: {
        id: number;
        titulo: string;
        precio: number | string;
        unidad: string;
    } | null;
    usuario: {
        id: number;
        name: string;
        email?: string;
        telefono?: string | null;
    } | null;
}

type Accion = 'aceptada' | 'rechazada';

const PESTANAS: { valor: EstadoSolicitud; etiqueta: string }[] = [
    { valor: 'pendiente', etiqueta: 'Pendientes' },
    { valor: 'aceptada', etiqueta: 'Aceptadas' },
    { valor: 'rechazada', etiqueta: 'Rechazadas' },
];

const VACIO: Record<EstadoSolicitud, string> = {
    pendiente: 'No tenés solicitudes por responder.',
    aceptada: 'Todavía no aceptaste ninguna solicitud.',
    rechazada: 'No rechazaste ninguna solicitud.',
};

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
});

// Panel "Mis solicitudes recibidas" del productor (KAN-151).
// Las pestañas filtran en el navegador: el backend ya manda todas las solicitudes.
export default function Index({
    solicitudes,
}: PageProps<{ solicitudes: Solicitud[] }>) {
    const { flash, errors } = usePage().props;

    const [pestana, setPestana] = useState<EstadoSolicitud>('pendiente');
    // Solicitud + acción que se está confirmando en el modal (null = modal cerrado).
    const [confirmando, setConfirmando] = useState<{
        solicitud: Solicitud;
        accion: Accion;
    } | null>(null);
    const [procesando, setProcesando] = useState(false);

    const cantidad = (estado: EstadoSolicitud) =>
        solicitudes.filter((s) => s.estado === estado).length;
    const visibles = solicitudes.filter((s) => s.estado === pestana);

    const confirmar = () => {
        if (!confirmando) return;

        const { solicitud, accion } = confirmando;

        router.patch(
            route('solicitudes-compra.resolve', solicitud.id),
            { estado: accion },
            {
                preserveScroll: true,
                onStart: () => setProcesando(true),
                onFinish: () => {
                    setProcesando(false);
                    setConfirmando(null);
                },
                // Al aceptar, se pasa a "Aceptadas" para ver el contacto del comprador.
                onSuccess: () => setPestana(accion),
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Solicitudes de compra recibidas
                </h2>
            }
        >
            <Head title="Solicitudes recibidas" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <p className="text-sm text-gray-600">
                        Acá ves quién quiere comprar tus publicaciones. Al
                        aceptar una solicitud aparecen los datos de contacto del
                        comprador para coordinar la entrega y el pago.
                    </p>

                    {flash.success && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}
                    {errors.estado && (
                        <div
                            role="alert"
                            className="rounded-md bg-red-50 p-4 text-sm text-red-800"
                        >
                            {errors.estado}
                        </div>
                    )}

                    <div
                        role="tablist"
                        aria-label="Filtrar solicitudes por estado"
                        className="flex flex-wrap gap-2"
                    >
                        {PESTANAS.map(({ valor, etiqueta }) => {
                            const activa = valor === pestana;

                            return (
                                <button
                                    key={valor}
                                    type="button"
                                    role="tab"
                                    aria-selected={activa}
                                    onClick={() => setPestana(valor)}
                                    className={`rounded-full px-4 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 ${
                                        activa
                                            ? 'bg-marca-azul-profundo text-white'
                                            : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100'
                                    }`}
                                >
                                    {etiqueta}{' '}
                                    <span
                                        className={
                                            activa
                                                ? 'text-white/80'
                                                : 'text-slate-400'
                                        }
                                    >
                                        ({cantidad(valor)})
                                    </span>
                                </button>
                            );
                        })}
                    </div>

                    {visibles.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 rounded-lg bg-white p-10 text-center shadow-sm">
                            <Inbox
                                className="h-8 w-8 text-slate-300"
                                aria-hidden="true"
                            />
                            <p className="text-sm text-gray-600">
                                {VACIO[pestana]}
                            </p>
                        </div>
                    ) : (
                        <ul className="space-y-4">
                            {visibles.map((solicitud) => (
                                <TarjetaSolicitud
                                    key={solicitud.id}
                                    solicitud={solicitud}
                                    onResponder={(accion) =>
                                        setConfirmando({ solicitud, accion })
                                    }
                                />
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <Modal
                show={confirmando !== null}
                onClose={() => !procesando && setConfirmando(null)}
                maxWidth="md"
            >
                {confirmando && (
                    <ContenidoConfirmacion
                        solicitud={confirmando.solicitud}
                        accion={confirmando.accion}
                        procesando={procesando}
                        onCancelar={() => setConfirmando(null)}
                        onConfirmar={confirmar}
                    />
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}

function TarjetaSolicitud({
    solicitud,
    onResponder,
}: {
    solicitud: Solicitud;
    onResponder: (accion: Accion) => void;
}) {
    const { publicacion, usuario } = solicitud;

    return (
        <li className="space-y-3 rounded-lg bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    {publicacion ? (
                        <Link
                            href={route('publicaciones.show', publicacion.id)}
                            className="font-medium text-gray-900 hover:underline"
                        >
                            {publicacion.titulo}
                        </Link>
                    ) : (
                        <span className="font-medium text-gray-500">
                            Publicación eliminada
                        </span>
                    )}
                    {publicacion && (
                        <p className="text-sm text-gray-500">
                            {formatearPrecio(publicacion.precio)} por{' '}
                            {publicacion.unidad}
                        </p>
                    )}
                </div>
                <p className="text-xs text-gray-500">
                    {formatoFecha.format(new Date(solicitud.created_at))}
                </p>
            </div>

            <p className="text-sm text-gray-700">
                <span className="text-gray-500">Comprador:</span>{' '}
                <span className="font-medium">
                    {usuario?.name ?? 'Usuario eliminado'}
                </span>
            </p>

            {solicitud.mensaje ? (
                // whitespace-pre-line respeta los saltos de línea del comprador.
                <blockquote className="whitespace-pre-line rounded-md border-l-4 border-slate-200 bg-slate-50 px-4 py-2 text-sm text-gray-700">
                    {solicitud.mensaje}
                </blockquote>
            ) : (
                <p className="text-sm italic text-gray-500">
                    No dejó un mensaje.
                </p>
            )}

            {solicitud.estado === 'pendiente' && (
                <div className="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-3">
                    <SecondaryButton onClick={() => onResponder('rechazada')}>
                        Rechazar
                    </SecondaryButton>
                    <PrimaryButton onClick={() => onResponder('aceptada')}>
                        Aceptar
                    </PrimaryButton>
                </div>
            )}

            {solicitud.estado === 'aceptada' && usuario && (
                <div className="space-y-1 rounded-md bg-green-50 p-3 text-sm text-green-900">
                    <p className="font-medium">
                        Contacto para coordinar la entrega y el pago
                    </p>
                    {usuario.telefono ? (
                        <a
                            href={`tel:${usuario.telefono}`}
                            className="flex items-center gap-2 hover:underline"
                        >
                            <Phone className="h-4 w-4" aria-hidden="true" />
                            {usuario.telefono}
                        </a>
                    ) : (
                        <p className="flex items-center gap-2 opacity-75">
                            <Phone className="h-4 w-4" aria-hidden="true" />
                            No cargó un teléfono.
                        </p>
                    )}
                    {usuario.email && (
                        <a
                            href={`mailto:${usuario.email}`}
                            className="flex items-center gap-2 hover:underline"
                        >
                            <Mail className="h-4 w-4" aria-hidden="true" />
                            {usuario.email}
                        </a>
                    )}
                </div>
            )}

            {solicitud.estado === 'rechazada' && (
                <p className="text-sm text-gray-500">
                    La rechazaste el{' '}
                    {formatoFecha.format(new Date(solicitud.updated_at))}.
                </p>
            )}
        </li>
    );
}

// Las dos acciones se confirman porque no se pueden deshacer:
// el backend solo deja responder solicitudes pendientes.
function ContenidoConfirmacion({
    solicitud,
    accion,
    procesando,
    onCancelar,
    onConfirmar,
}: {
    solicitud: Solicitud;
    accion: Accion;
    procesando: boolean;
    onCancelar: () => void;
    onConfirmar: () => void;
}) {
    const esAceptar = accion === 'aceptada';

    return (
        <div className="p-6">
            <h2 className="text-lg font-medium text-gray-900">
                {esAceptar
                    ? '¿Aceptar la solicitud de compra?'
                    : '¿Rechazar la solicitud de compra?'}
            </h2>

            <dl className="mt-4 grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 rounded-lg bg-slate-50 p-4 text-sm">
                <dt className="text-slate-500">Publicación</dt>
                <dd className="font-medium text-slate-900">
                    {solicitud.publicacion?.titulo ?? 'Publicación eliminada'}
                </dd>
                <dt className="text-slate-500">Comprador</dt>
                <dd className="text-slate-900">
                    {solicitud.usuario?.name ?? 'Usuario eliminado'}
                </dd>
            </dl>

            <p className="mt-4 text-sm text-gray-600">
                {esAceptar
                    ? 'Vas a ver el teléfono y el email del comprador para coordinar la entrega y el pago. El stock no se descuenta solo: actualizalo cuando concretes la venta.'
                    : 'El comprador va a ver que rechazaste su solicitud y podrá enviarte otra si quiere.'}{' '}
                No se puede deshacer.
            </p>

            <div className="mt-6 flex justify-end gap-3">
                <SecondaryButton onClick={onCancelar} disabled={procesando}>
                    Cancelar
                </SecondaryButton>
                {esAceptar ? (
                    <PrimaryButton onClick={onConfirmar} disabled={procesando}>
                        Sí, aceptar
                    </PrimaryButton>
                ) : (
                    <DangerButton onClick={onConfirmar} disabled={procesando}>
                        Sí, rechazar
                    </DangerButton>
                )}
            </div>
        </div>
    );
}
