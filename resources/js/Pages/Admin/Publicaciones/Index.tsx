import {
    ModalBloquear,
    ModalEliminar,
} from '@/Components/Admin/ModalesModeracion';
import AdminLayout from '@/Layouts/AdminLayout';
import { Categoria, EstadoPublicacion, Publicacion, User } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Ban, Eye, LockOpen, Search, Trash2 } from 'lucide-react';
import { CSSProperties, useEffect, useRef, useState } from 'react';

// Sección "Publicaciones" del panel admin: moderación (KAN-222).
// Lo que manda PublicacionModeracionController::index(): todas las publicaciones
// (de cualquier estado), filtradas por ?q=, ?estado= y ?categoria_id=, de a 20.

type Fila = Pick<
    Publicacion,
    'id' | 'titulo' | 'estado' | 'motivo_bloqueo' | 'bloqueada_at'
> & {
    created_at: string;
    usuario: Pick<User, 'id' | 'name' | 'email'> | null;
    categoria: Pick<Categoria, 'id' | 'nombre'> | null;
    bloqueada_por: Pick<User, 'id' | 'name'> | null;
};

type Paginado<T> = {
    data: T[];
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

type Filtros = { q?: string; estado?: string; categoria_id?: string };

const ESTADOS: { valor: EstadoPublicacion; texto: string; clases: string }[] = [
    { valor: 'activa', texto: 'Activa', clases: 'bg-green-100 text-green-800' },
    {
        valor: 'pausada',
        texto: 'Pausada',
        clases: 'bg-amber-100 text-amber-800',
    },
    {
        valor: 'vendida',
        texto: 'Vendida',
        clases: 'bg-slate-100 text-slate-600',
    },
    {
        valor: 'bloqueada',
        texto: 'Bloqueada',
        clases: 'bg-red-100 text-red-800',
    },
];

const formatoDia = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const CLASES_CAMPO =
    'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600';

// Las etiquetas de la paginación vienen de lang/es/pagination.php con entidades HTML.
const textoPagina = (label: string) =>
    label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Index({
    publicaciones,
    categorias,
    filtros,
}: {
    publicaciones: Paginado<Fila>;
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
    filtros: Filtros;
}) {
    const [busqueda, setBusqueda] = useState(filtros.q ?? '');
    const [aBloquear, setABloquear] = useState<Fila | null>(null);
    const [aEliminar, setAEliminar] = useState<Fila | null>(null);
    const [procesandoId, setProcesandoId] = useState<number | null>(null);

    // Pide la página con los filtros, sin los vacíos y volviendo a la página 1.
    // "replace" no llena el historial con cada letra del buscador.
    const filtrar = (cambios: Filtros) => {
        const nuevos = { ...filtros, ...cambios };
        const params = Object.fromEntries(
            Object.entries(nuevos).filter(([, v]) => v),
        );

        router.get(route('admin.publicaciones.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // El buscador filtra solo, cuando se deja de escribir (400 ms).
    const primeraVez = useRef(true);
    useEffect(() => {
        if (primeraVez.current) {
            primeraVez.current = false;
            return;
        }
        const espera = setTimeout(() => filtrar({ q: busqueda.trim() }), 400);
        return () => clearTimeout(espera);
        // Solo depende del texto: los demás filtros se leen al momento de pedir.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [busqueda]);

    const desbloquear = (fila: Fila) => {
        router.patch(
            route('admin.publicaciones.desbloquear', fila.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setProcesandoId(fila.id),
                onFinish: () => setProcesandoId(null),
            },
        );
    };

    const hayFiltros = Boolean(
        filtros.q || filtros.estado || filtros.categoria_id,
    );

    return (
        <AdminLayout titulo="Publicaciones">
            <div className="mx-auto max-w-7xl space-y-6">
                {/* Buscador y filtros */}
                <div className="grid gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-[minmax(0,1fr)_11rem_13rem]">
                    <label className="relative block">
                        <span className="sr-only">Buscar publicaciones</span>
                        <Search
                            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            aria-hidden="true"
                        />
                        <input
                            type="search"
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            placeholder="Buscar por título o descripción"
                            className={`${CLASES_CAMPO} pl-9`}
                        />
                    </label>

                    <label>
                        <span className="sr-only">Filtrar por estado</span>
                        <select
                            value={filtros.estado ?? ''}
                            onChange={(e) =>
                                filtrar({ estado: e.target.value })
                            }
                            className={CLASES_CAMPO}
                        >
                            <option value="">Todos los estados</option>
                            {ESTADOS.map((e) => (
                                <option key={e.valor} value={e.valor}>
                                    {e.texto}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label>
                        <span className="sr-only">Filtrar por categoría</span>
                        <select
                            value={filtros.categoria_id ?? ''}
                            onChange={(e) =>
                                filtrar({ categoria_id: e.target.value })
                            }
                            className={CLASES_CAMPO}
                        >
                            <option value="">Todas las categorías</option>
                            {categorias.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.nombre}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>

                <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    {publicaciones.data.length === 0 ? (
                        <div className="p-6 text-sm text-slate-600">
                            {hayFiltros ? (
                                <>
                                    No hay publicaciones con esos filtros.{' '}
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setBusqueda('');
                                            router.get(
                                                route(
                                                    'admin.publicaciones.index',
                                                ),
                                            );
                                        }}
                                        className="font-medium text-marca-azul hover:underline"
                                    >
                                        Limpiar filtros
                                    </button>
                                </>
                            ) : (
                                'Todavía no hay publicaciones.'
                            )}
                        </div>
                    ) : (
                        // En pantallas chicas la tabla se desplaza de costado dentro de su recuadro.
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th scope="col" className="px-4 py-3">
                                            Publicación
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Productor
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Categoría
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Estado
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Publicada
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
                                    {publicaciones.data.map((fila, i) => (
                                        <FilaPublicacion
                                            key={fila.id}
                                            fila={fila}
                                            orden={i}
                                            procesando={
                                                procesandoId === fila.id
                                            }
                                            onBloquear={() =>
                                                setABloquear(fila)
                                            }
                                            onDesbloquear={() =>
                                                desbloquear(fila)
                                            }
                                            onEliminar={() =>
                                                setAEliminar(fila)
                                            }
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {publicaciones.total > 0 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-xs text-slate-600">
                            {publicaciones.from}–{publicaciones.to} de{' '}
                            {publicaciones.total}{' '}
                            {publicaciones.total === 1
                                ? 'publicación'
                                : 'publicaciones'}
                        </p>

                        {publicaciones.links.length > 3 && (
                            <nav
                                aria-label="Páginas"
                                className="flex flex-wrap gap-1"
                            >
                                {publicaciones.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            preserveScroll
                                            preserveState
                                            aria-current={
                                                link.active ? 'page' : undefined
                                            }
                                            className={`rounded-md px-3 py-1.5 text-xs font-medium transition ${
                                                link.active
                                                    ? 'bg-marca-azul-profundo text-white'
                                                    : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100'
                                            }`}
                                        >
                                            {textoPagina(link.label)}
                                        </Link>
                                    ) : (
                                        <span
                                            key={i}
                                            className="rounded-md px-3 py-1.5 text-xs text-slate-300"
                                        >
                                            {textoPagina(link.label)}
                                        </span>
                                    ),
                                )}
                            </nav>
                        )}
                    </div>
                )}
            </div>

            <ModalBloquear
                publicacion={aBloquear}
                onCerrar={() => setABloquear(null)}
            />
            <ModalEliminar
                publicacion={aEliminar}
                onCerrar={() => setAEliminar(null)}
            />
        </AdminLayout>
    );
}

function FilaPublicacion({
    fila,
    orden,
    procesando,
    onBloquear,
    onDesbloquear,
    onEliminar,
}: {
    fila: Fila;
    orden: number;
    procesando: boolean;
    onBloquear: () => void;
    onDesbloquear: () => void;
    onEliminar: () => void;
}) {
    const estado = ESTADOS.find((e) => e.valor === fila.estado) ?? ESTADOS[0];
    const bloqueada = fila.estado === 'bloqueada';

    const base =
        'inline-flex items-center gap-1.5 whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-1 disabled:opacity-50';

    return (
        <tr
            className="aparecer align-top"
            style={{ '--orden': orden } as CSSProperties}
        >
            <td className="max-w-xs px-4 py-4">
                <p className="font-medium text-slate-900">{fila.titulo}</p>
                {bloqueada && fila.motivo_bloqueo && (
                    <p className="mt-1 text-xs text-red-700">
                        <span className="font-semibold">Motivo:</span>{' '}
                        {fila.motivo_bloqueo}
                        {fila.bloqueada_por && (
                            <span className="text-red-600/80">
                                {' '}
                                · por {fila.bloqueada_por.name}
                            </span>
                        )}
                    </p>
                )}
            </td>

            <td className="px-4 py-4">
                <p>{fila.usuario?.name ?? '—'}</p>
                {fila.usuario?.email && (
                    <p className="break-all text-xs text-slate-500">
                        {fila.usuario.email}
                    </p>
                )}
            </td>

            <td className="px-4 py-4">{fila.categoria?.nombre ?? '—'}</td>

            <td className="px-4 py-4">
                <span
                    className={`inline-block whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ${estado.clases}`}
                >
                    {estado.texto}
                </span>
            </td>

            <td className="whitespace-nowrap px-4 py-4 text-slate-500">
                {formatoDia.format(new Date(fila.created_at))}
            </td>

            <td className="px-4 py-4">
                <div className="flex flex-wrap justify-end gap-2">
                    <Link
                        href={route('publicaciones.show', fila.id)}
                        className={`${base} bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50`}
                    >
                        <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                        Ver
                    </Link>

                    {bloqueada ? (
                        <button
                            type="button"
                            onClick={onDesbloquear}
                            disabled={procesando}
                            title="Vuelve a 'pausada': el productor decide cuándo reactivarla"
                            className={`${base} bg-white text-green-700 ring-1 ring-green-300 hover:bg-green-50`}
                        >
                            <LockOpen
                                className="h-3.5 w-3.5"
                                aria-hidden="true"
                            />
                            Desbloquear
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={onBloquear}
                            disabled={procesando}
                            className={`${base} bg-white text-red-700 ring-1 ring-red-300 hover:bg-red-50`}
                        >
                            <Ban className="h-3.5 w-3.5" aria-hidden="true" />
                            Bloquear
                        </button>
                    )}

                    <button
                        type="button"
                        onClick={onEliminar}
                        disabled={procesando}
                        aria-label={`Eliminar “${fila.titulo}”`}
                        className={`${base} bg-red-600 text-white hover:bg-red-700`}
                    >
                        <Trash2 className="h-3.5 w-3.5" aria-hidden="true" />
                        Eliminar
                    </button>
                </div>
            </td>
        </tr>
    );
}
