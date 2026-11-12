import AdminLayout from '@/Layouts/AdminLayout';
import { Denuncia, EstadoDenuncia } from '@/types';
import { Link, router } from '@inertiajs/react';
import { CSSProperties } from 'react';

// Pestañas de filtro (?estado=). Sin parámetro, el backend muestra las pendientes.
// No hay pestaña "Todas": el backend no tiene un valor para "sin filtro"
// (un ?estado= vacío vuelve a "pendiente").
const FILTROS: { valor: EstadoDenuncia; etiqueta: string }[] = [
    { valor: 'pendiente', etiqueta: 'Pendientes' },
    { valor: 'revisada', etiqueta: 'Revisadas' },
    { valor: 'descartada', etiqueta: 'Descartadas' },
];

const ESTILO_ESTADO: Record<EstadoDenuncia, { texto: string; clases: string }> =
    {
        pendiente: {
            texto: 'Pendiente',
            clases: 'bg-amber-100 text-amber-800',
        },
        revisada: { texto: 'Revisada', clases: 'bg-green-100 text-green-800' },
        descartada: {
            texto: 'Descartada',
            clases: 'bg-slate-100 text-slate-600',
        },
    };

const MENSAJE_VACIO: Record<EstadoDenuncia, string> = {
    pendiente: 'No hay denuncias pendientes. ¡Todo en orden!',
    revisada: 'Todavía no hay denuncias revisadas.',
    descartada: 'Todavía no hay denuncias descartadas.',
};

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

export default function Index({
    denuncias,
    estado_actual,
}: {
    denuncias: Denuncia[];
    // El estado por el que filtró el backend (por defecto, "pendiente").
    estado_actual: EstadoDenuncia;
}) {
    const filtroActivo = estado_actual;

    const filtrar = (valor: EstadoDenuncia) => {
        router.get(
            route('admin.denuncias.index'),
            { estado: valor },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <AdminLayout titulo="Denuncias">
            <div className="mx-auto max-w-7xl space-y-6">
                {/* Pestañas de filtro por estado */}
                <div
                    role="tablist"
                    aria-label="Filtrar denuncias por estado"
                    className="flex flex-wrap gap-2"
                >
                    {FILTROS.map((filtro) => {
                        const activo = filtro.valor === filtroActivo;

                        return (
                            <button
                                key={filtro.valor}
                                type="button"
                                role="tab"
                                aria-selected={activo}
                                onClick={() => filtrar(filtro.valor)}
                                className={`rounded-full px-4 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 ${
                                    activo
                                        ? 'bg-sky-800 text-white'
                                        : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100'
                                }`}
                            >
                                {filtro.etiqueta}
                            </button>
                        );
                    })}
                </div>

                <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    {denuncias.length === 0 ? (
                        <p className="p-6 text-sm text-slate-600">
                            {MENSAJE_VACIO[filtroActivo]}
                        </p>
                    ) : (
                        // En pantallas chicas la tabla se desplaza de costado dentro de su recuadro.
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th scope="col" className="px-5 py-3">
                                            Fecha
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Publicación denunciada
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Motivo
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Denunciado por
                                        </th>
                                        <th scope="col" className="px-5 py-3">
                                            Estado
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100 text-slate-700">
                                    {denuncias.map((denuncia, i) => {
                                        const estado =
                                            ESTILO_ESTADO[denuncia.estado];

                                        return (
                                            <tr
                                                key={denuncia.id}
                                                className="aparecer align-top"
                                                style={
                                                    {
                                                        '--orden': i,
                                                    } as CSSProperties
                                                }
                                            >
                                                <td className="whitespace-nowrap px-5 py-4 text-slate-500">
                                                    {formatoFecha.format(
                                                        new Date(
                                                            denuncia.created_at,
                                                        ),
                                                    )}
                                                </td>

                                                <td className="min-w-48 px-5 py-4">
                                                    {denuncia.publicacion ? (
                                                        <>
                                                            <Link
                                                                href={route(
                                                                    'publicaciones.show',
                                                                    denuncia
                                                                        .publicacion
                                                                        .id,
                                                                )}
                                                                className="font-medium text-sky-700 hover:underline"
                                                            >
                                                                {
                                                                    denuncia
                                                                        .publicacion
                                                                        .titulo
                                                                }
                                                            </Link>
                                                            <p className="text-xs text-slate-500">
                                                                de{' '}
                                                                {denuncia
                                                                    .publicacion
                                                                    .usuario
                                                                    ?.name ??
                                                                    'usuario desconocido'}
                                                            </p>
                                                        </>
                                                    ) : (
                                                        <span className="text-slate-400">
                                                            Publicación
                                                            eliminada
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="min-w-64 max-w-md px-5 py-4">
                                                    <p className="font-medium text-slate-900">
                                                        {denuncia.motivo}
                                                    </p>
                                                    {denuncia.descripcion && (
                                                        <p className="mt-1 whitespace-pre-line text-slate-600">
                                                            {
                                                                denuncia.descripcion
                                                            }
                                                        </p>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <p>
                                                        {denuncia.usuario
                                                            ?.name ?? '—'}
                                                    </p>
                                                    {denuncia.usuario
                                                        ?.email && (
                                                        <p className="text-xs text-slate-500">
                                                            {
                                                                denuncia.usuario
                                                                    .email
                                                            }
                                                        </p>
                                                    )}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <span
                                                        className={`inline-block whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ${estado.clases}`}
                                                    >
                                                        {estado.texto}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {denuncias.length > 0 && (
                    <p className="text-xs text-slate-500">
                        {denuncias.length}{' '}
                        {denuncias.length === 1 ? 'denuncia' : 'denuncias'}
                    </p>
                )}
            </div>
        </AdminLayout>
    );
}
