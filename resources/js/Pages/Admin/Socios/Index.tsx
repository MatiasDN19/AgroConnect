import BotonExportar from '@/Components/Admin/BotonExportar';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AdminLayout from '@/Layouts/AdminLayout';
import { User } from '@/types';
import { router } from '@inertiajs/react';
import { BadgeCheck, Check, X } from 'lucide-react';
import { CSSProperties, useState } from 'react';

// Lo que manda SocioController::index(): usuarios con número de socio cargado
// y todavía sin validar.
type Solicitud = Pick<
    User,
    'id' | 'name' | 'email' | 'telefono' | 'numero_socio'
> & {
    updated_at: string;
};

type Accion = 'aceptar' | 'rechazar';

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

export default function Index({
    solicitudes = [],
}: {
    solicitudes: Solicitud[];
}) {
    // Solicitud + acción que se está confirmando en el modal (null = modal cerrado).
    const [confirmando, setConfirmando] = useState<{
        solicitud: Solicitud;
        accion: Accion;
    } | null>(null);
    const [procesando, setProcesando] = useState(false);

    const confirmar = () => {
        if (!confirmando) return;

        const { solicitud, accion } = confirmando;

        router.post(
            route(`admin.socios.${accion}`, solicitud.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setProcesando(true),
                onFinish: () => {
                    setProcesando(false);
                    setConfirmando(null);
                },
            },
        );
    };

    return (
        <AdminLayout
            titulo="Socios del Consorcio"
            acciones={
                // Baja los socios ya validados (KAN-318).
                <BotonExportar
                    href={route('admin.exportar.socios')}
                    etiqueta="Exportar socios"
                />
            }
        >
            <div className="mx-auto max-w-7xl space-y-6">
                <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="border-b border-slate-200 px-5 py-4">
                        <h2 className="text-base font-semibold text-slate-800">
                            Solicitudes pendientes de validación
                        </h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Productores que cargaron su número de socio y
                            esperan que el Consorcio lo confirme. Al aceptar,
                            sus publicaciones pasan a aparecer primero en las
                            búsquedas.
                        </p>
                    </div>

                    {solicitudes.length === 0 ? (
                        <p className="px-5 py-10 text-center text-sm text-slate-500">
                            No hay solicitudes de socio pendientes.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th scope="col" className="px-4 py-3">
                                            Productor
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Número de socio
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Solicitado
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-4 py-3 text-right"
                                        >
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100 text-slate-700">
                                    {solicitudes.map((solicitud, i) => (
                                        <tr
                                            key={solicitud.id}
                                            className="aparecer align-top"
                                            style={
                                                {
                                                    '--orden': i,
                                                } as CSSProperties
                                            }
                                        >
                                            <td className="px-4 py-4">
                                                <p className="font-medium text-slate-900">
                                                    {solicitud.name}
                                                </p>
                                                <p className="break-all text-xs text-slate-500">
                                                    {solicitud.email}
                                                </p>
                                                {solicitud.telefono && (
                                                    <p className="text-xs text-slate-500">
                                                        {solicitud.telefono}
                                                    </p>
                                                )}
                                            </td>

                                            <td className="px-4 py-4">
                                                <span className="rounded-md bg-slate-100 px-2 py-1 font-mono text-slate-800">
                                                    {solicitud.numero_socio}
                                                </span>
                                            </td>

                                            <td className="whitespace-nowrap px-4 py-4 text-slate-500">
                                                {formatoFecha.format(
                                                    new Date(
                                                        solicitud.updated_at,
                                                    ),
                                                )}
                                            </td>

                                            <td className="px-4 py-4 text-right">
                                                <div className="flex flex-wrap justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setConfirmando({
                                                                solicitud,
                                                                accion: 'aceptar',
                                                            })
                                                        }
                                                        className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-1"
                                                    >
                                                        <Check
                                                            className="h-3.5 w-3.5"
                                                            aria-hidden="true"
                                                        />
                                                        Aceptar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setConfirmando({
                                                                solicitud,
                                                                accion: 'rechazar',
                                                            })
                                                        }
                                                        className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-red-700 ring-1 ring-red-200 transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-1"
                                                    >
                                                        <X
                                                            className="h-3.5 w-3.5"
                                                            aria-hidden="true"
                                                        />
                                                        Rechazar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {solicitudes.length > 0 && (
                    <p className="text-xs text-slate-500">
                        {solicitudes.length}{' '}
                        {solicitudes.length === 1
                            ? 'solicitud pendiente'
                            : 'solicitudes pendientes'}
                    </p>
                )}
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
        </AdminLayout>
    );
}

// Texto y botones del modal según la acción. Aceptar también se confirma
// porque, una vez validado, el socio ya no aparece en esta lista y no se
// puede deshacer desde el panel.
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
    const esAceptar = accion === 'aceptar';

    return (
        <div className="p-6">
            <h2 className="text-lg font-medium text-gray-900">
                {esAceptar
                    ? '¿Validar como socio del Consorcio?'
                    : '¿Rechazar la solicitud de socio?'}
            </h2>

            <dl className="mt-4 grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 rounded-lg bg-slate-50 p-4 text-sm">
                <dt className="text-slate-500">Productor</dt>
                <dd className="font-medium text-slate-900">{solicitud.name}</dd>
                <dt className="text-slate-500">Número de socio</dt>
                <dd className="font-mono text-slate-900">
                    {solicitud.numero_socio}
                </dd>
            </dl>

            <p className="mt-4 text-sm text-gray-600">
                {esAceptar
                    ? 'Revisá que el número corresponda a un socio real. Sus publicaciones van a mostrar la insignia de socio y aparecer primero en las búsquedas.'
                    : 'Se va a borrar el número de socio que cargó. Si fue un error de tipeo, va a poder volver a enviarlo desde su perfil.'}
            </p>

            <div className="mt-6 flex justify-end gap-3">
                <SecondaryButton onClick={onCancelar} disabled={procesando}>
                    Cancelar
                </SecondaryButton>

                {esAceptar ? (
                    <button
                        type="button"
                        onClick={onConfirmar}
                        disabled={procesando}
                        className="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        <BadgeCheck className="h-4 w-4" aria-hidden="true" />
                        Validar socio
                    </button>
                ) : (
                    <DangerButton onClick={onConfirmar} disabled={procesando}>
                        Rechazar solicitud
                    </DangerButton>
                )}
            </div>
        </div>
    );
}
