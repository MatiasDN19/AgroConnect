import BotonExportar from '@/Components/Admin/BotonExportar';
import Avatar from '@/Components/Avatar';
import {
    BotonGuardar,
    BotonPeligro,
    BotonSecundario,
} from '@/Components/Formulario';
import Modal from '@/Components/Modal';
import SocioBadge from '@/Components/SocioBadge';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageProps, Paginado, User } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Ban,
    RotateCcw,
    Search,
    ShieldMinus,
    ShieldPlus,
    UserX,
} from 'lucide-react';
import { CSSProperties, useEffect, useRef, useState } from 'react';

// Usuarios del panel admin (KAN-119). Backend: KAN-245/246 (UsuarioController).
// Inhabilitar corta el acceso al instante: cierra sus sesiones y revoca los
// tokens de la app (KAN-294). La acción de dar/quitar admin es KAN-228.

interface Fila {
    id: number;
    name: string;
    email: string;
    rol: User['rol'];
    telefono: string | null;
    socio_validado: boolean;
    inhabilitado: boolean;
    created_at: string;
    publicaciones_count: number;
}

type Filtros = { q?: string; rol?: string; inhabilitado?: string };

const ROLES: { valor: User['rol']; texto: string; chip: string }[] = [
    {
        valor: 'productor',
        texto: 'Productor',
        chip: 'bg-green-50 text-green-700 ring-green-200',
    },
    {
        valor: 'comprador',
        texto: 'Comprador',
        chip: 'bg-sky-50 text-sky-700 ring-sky-200',
    },
    {
        valor: 'admin',
        texto: 'Administrador',
        chip: 'bg-violet-50 text-violet-700 ring-violet-200',
    },
];

const CLASES_CAMPO =
    'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-600 focus:ring-sky-600';

// Base de los botones chicos de la columna Acciones.
const CLASE_ACCION =
    'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 disabled:opacity-50';

const formatoAlta = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

// Las etiquetas de la paginación vienen de lang/es/pagination.php con entidades HTML.
const textoPagina = (label: string) =>
    label.replace('&laquo;', '«').replace('&raquo;', '»');

export default function Index({
    usuarios,
    filtros,
}: {
    usuarios: Paginado<Fila>;
    filtros: Filtros;
}) {
    const yo = usePage<PageProps>().props.auth.user;
    const [busqueda, setBusqueda] = useState(filtros.q ?? '');
    const [aInhabilitar, setAInhabilitar] = useState<Fila | null>(null);
    const [procesandoId, setProcesandoId] = useState<number | null>(null);
    // Dar o quitar admin (KAN-228): cada una con su modal.
    const [aHacerAdmin, setAHacerAdmin] = useState<Fila | null>(null);
    const [aQuitarAdmin, setAQuitarAdmin] = useState<Fila | null>(null);
    const [rolNuevo, setRolNuevo] = useState<'productor' | 'comprador'>(
        'productor',
    );

    const cambiarRol = (fila: Fila, rol: User['rol']) => {
        router.patch(
            route('admin.usuarios.rol', fila.id),
            { rol },
            {
                preserveScroll: true,
                onStart: () => setProcesandoId(fila.id),
                onFinish: () => {
                    setProcesandoId(null);
                    setAHacerAdmin(null);
                    setAQuitarAdmin(null);
                },
            },
        );
    };

    // Pide la página con los filtros, sin los vacíos y volviendo a la página 1.
    // "replace" no llena el historial con cada letra del buscador.
    const filtrar = (cambios: Filtros) => {
        const nuevos = { ...filtros, ...cambios };
        const params = Object.fromEntries(
            Object.entries(nuevos).filter(([, v]) => v !== '' && v != null),
        );
        router.get(route('admin.usuarios.index'), params, {
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

    // inhabilitado: true inhabilita, false reactiva (el backend también acepta
    // alternar sin el dato, pero así queda explícito).
    const cambiarEstado = (fila: Fila, inhabilitado: boolean) => {
        router.patch(
            route('admin.usuarios.inhabilitar', fila.id),
            { inhabilitado },
            {
                preserveScroll: true,
                onStart: () => setProcesandoId(fila.id),
                onFinish: () => {
                    setProcesandoId(null);
                    setAInhabilitar(null);
                },
            },
        );
    };

    const hayFiltros = Boolean(
        filtros.q || filtros.rol || filtros.inhabilitado,
    );
    const filtrosExportar = Object.fromEntries(
        Object.entries(filtros).filter(([, v]) => v !== '' && v != null),
    );

    return (
        <AdminLayout
            titulo="Usuarios"
            acciones={
                // Exporta lo mismo que se ve, con los filtros (KAN-318).
                <BotonExportar
                    href={route('admin.exportar.usuarios', filtrosExportar)}
                />
            }
        >
            <div className="mx-auto max-w-7xl space-y-6">
                {/* Buscador y filtros */}
                <div className="grid gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-[minmax(0,1fr)_11rem_11rem]">
                    <label className="relative block">
                        <span className="sr-only">Buscar usuarios</span>
                        <Search
                            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            aria-hidden="true"
                        />
                        <input
                            type="search"
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            placeholder="Buscar por nombre o email"
                            className={`${CLASES_CAMPO} pl-9`}
                        />
                    </label>

                    <label>
                        <span className="sr-only">Filtrar por rol</span>
                        <select
                            value={filtros.rol ?? ''}
                            onChange={(e) => filtrar({ rol: e.target.value })}
                            className={CLASES_CAMPO}
                        >
                            <option value="">Todos los roles</option>
                            {ROLES.map((r) => (
                                <option key={r.valor} value={r.valor}>
                                    {r.texto}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label>
                        <span className="sr-only">Filtrar por estado</span>
                        <select
                            value={filtros.inhabilitado ?? ''}
                            onChange={(e) =>
                                filtrar({ inhabilitado: e.target.value })
                            }
                            className={CLASES_CAMPO}
                        >
                            <option value="">Todos los estados</option>
                            <option value="0">Activos</option>
                            <option value="1">Inhabilitados</option>
                        </select>
                    </label>
                </div>

                <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    {usuarios.data.length === 0 ? (
                        <div className="p-6 text-sm text-slate-600">
                            {hayFiltros ? (
                                <>
                                    No hay usuarios con esos filtros.{' '}
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setBusqueda('');
                                            router.get(
                                                route('admin.usuarios.index'),
                                            );
                                        }}
                                        className="font-medium text-marca-azul hover:underline"
                                    >
                                        Limpiar filtros
                                    </button>
                                </>
                            ) : (
                                'Todavía no hay usuarios.'
                            )}
                        </div>
                    ) : (
                        // En pantallas chicas la tabla se desplaza de costado dentro de su recuadro.
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th scope="col" className="px-4 py-3">
                                            Usuario
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Rol
                                        </th>
                                        {/* Publicaciones y Alta solo en pantallas anchas: así
                                            Estado y Acciones se ven sin desplazar la tabla. */}
                                        <th
                                            scope="col"
                                            className="hidden px-4 py-3 text-right xl:table-cell"
                                        >
                                            Publicaciones
                                        </th>
                                        <th
                                            scope="col"
                                            className="hidden px-4 py-3 xl:table-cell"
                                        >
                                            Alta
                                        </th>
                                        <th scope="col" className="px-4 py-3">
                                            Estado
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
                                    {usuarios.data.map((fila, i) => (
                                        <FilaUsuario
                                            key={fila.id}
                                            fila={fila}
                                            orden={i}
                                            esYo={fila.id === yo.id}
                                            procesando={
                                                procesandoId === fila.id
                                            }
                                            onInhabilitar={() =>
                                                setAInhabilitar(fila)
                                            }
                                            onReactivar={() =>
                                                cambiarEstado(fila, false)
                                            }
                                            onHacerAdmin={() =>
                                                setAHacerAdmin(fila)
                                            }
                                            onQuitarAdmin={() => {
                                                setRolNuevo('productor');
                                                setAQuitarAdmin(fila);
                                            }}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {usuarios.total > 0 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-xs text-slate-600">
                            {usuarios.from}–{usuarios.to} de {usuarios.total}{' '}
                            {usuarios.total === 1 ? 'usuario' : 'usuarios'}
                        </p>

                        {usuarios.links.length > 3 && (
                            <nav
                                aria-label="Páginas"
                                className="flex flex-wrap gap-1"
                            >
                                {usuarios.links.map((link, i) =>
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

            {/* Confirmación antes de inhabilitar: el usuario deja de poder entrar. */}
            <Modal
                show={aInhabilitar !== null}
                onClose={() => setAInhabilitar(null)}
            >
                <div className="p-6">
                    <div className="flex gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                            <UserX className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 className="font-display text-lg font-bold text-marca-tinta">
                                ¿Inhabilitar a {aInhabilitar?.name}?
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                No va a poder entrar a AgroConnect (ni en la web
                                ni en la app) y se le cierran las sesiones
                                abiertas. Podés reactivarlo cuando quieras.
                            </p>
                        </div>
                    </div>
                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <BotonSecundario onClick={() => setAInhabilitar(null)}>
                            Cancelar
                        </BotonSecundario>
                        <BotonPeligro
                            onClick={() =>
                                aInhabilitar &&
                                cambiarEstado(aInhabilitar, true)
                            }
                            disabled={procesandoId !== null}
                        >
                            Inhabilitar
                        </BotonPeligro>
                    </div>
                </div>
            </Modal>
            {/* Hacer admin (KAN-228): le da acceso a todo el panel, así que se confirma. */}
            <Modal
                show={aHacerAdmin !== null}
                onClose={() => setAHacerAdmin(null)}
            >
                <div className="p-6">
                    <div className="flex gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-700">
                            <ShieldPlus
                                className="h-5 w-5"
                                aria-hidden="true"
                            />
                        </span>
                        <div>
                            <h2 className="font-display text-lg font-bold text-marca-tinta">
                                ¿Hacer administrador a {aHacerAdmin?.name}?
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Su rol cambia de{' '}
                                {
                                    ROLES.find(
                                        (r) => r.valor === aHacerAdmin?.rol,
                                    )?.texto
                                }{' '}
                                a Administrador y va a poder moderar
                                publicaciones, inhabilitar cuentas y cambiar la
                                configuración de AgroConnect.
                            </p>
                        </div>
                    </div>
                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <BotonSecundario onClick={() => setAHacerAdmin(null)}>
                            Cancelar
                        </BotonSecundario>
                        <BotonGuardar
                            type="button"
                            onClick={() =>
                                aHacerAdmin && cambiarRol(aHacerAdmin, 'admin')
                            }
                            disabled={procesandoId !== null}
                        >
                            Hacer administrador
                        </BotonGuardar>
                    </div>
                </div>
            </Modal>

            {/* Quitar admin (KAN-228): hay que elegir el rol que pasa a tener. */}
            <Modal
                show={aQuitarAdmin !== null}
                onClose={() => setAQuitarAdmin(null)}
            >
                <div className="p-6">
                    <div className="flex gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                            <ShieldMinus
                                className="h-5 w-5"
                                aria-hidden="true"
                            />
                        </span>
                        <div>
                            <h2 className="font-display text-lg font-bold text-marca-tinta">
                                ¿Quitarle el rol de administrador a{' '}
                                {aQuitarAdmin?.name}?
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Deja de tener acceso al panel. Elegí qué rol
                                pasa a tener:
                            </p>
                        </div>
                    </div>

                    <fieldset className="mt-5 grid gap-3 sm:grid-cols-2">
                        <legend className="sr-only">Rol nuevo</legend>
                        {ROLES.filter((r) => r.valor !== 'admin').map((r) => (
                            <label
                                key={r.valor}
                                className={`flex cursor-pointer items-center gap-3 rounded-xl p-3.5 ring-1 transition-colors duration-150 ${
                                    rolNuevo === r.valor
                                        ? 'bg-sky-50 ring-2 ring-marca-azul'
                                        : 'ring-slate-200 hover:bg-slate-50'
                                }`}
                            >
                                <input
                                    type="radio"
                                    name="rol-nuevo"
                                    value={r.valor}
                                    checked={rolNuevo === r.valor}
                                    onChange={() =>
                                        setRolNuevo(
                                            r.valor as
                                                'productor' | 'comprador',
                                        )
                                    }
                                    className="text-marca-azul focus:ring-marca-azul"
                                />
                                <span className="text-sm font-medium text-slate-800">
                                    {r.texto}
                                </span>
                            </label>
                        ))}
                    </fieldset>

                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <BotonSecundario onClick={() => setAQuitarAdmin(null)}>
                            Cancelar
                        </BotonSecundario>
                        <BotonGuardar
                            type="button"
                            onClick={() =>
                                aQuitarAdmin &&
                                cambiarRol(aQuitarAdmin, rolNuevo)
                            }
                            disabled={procesandoId !== null}
                        >
                            Quitar admin
                        </BotonGuardar>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

function FilaUsuario({
    fila,
    orden,
    esYo,
    procesando,
    onInhabilitar,
    onReactivar,
    onHacerAdmin,
    onQuitarAdmin,
}: {
    fila: Fila;
    orden: number;
    esYo: boolean;
    procesando: boolean;
    onInhabilitar: () => void;
    onReactivar: () => void;
    onHacerAdmin: () => void;
    onQuitarAdmin: () => void;
}) {
    const rol = ROLES.find((r) => r.valor === fila.rol);

    return (
        <tr
            className={`aparecer ${fila.inhabilitado ? 'bg-slate-50/70' : ''}`}
            style={{ '--orden': orden } as CSSProperties}
        >
            <td className="px-4 py-3">
                <div className="flex items-center gap-3">
                    <span className={fila.inhabilitado ? 'opacity-50' : ''}>
                        <Avatar nombre={fila.name} />
                    </span>
                    <div className="min-w-0">
                        <p className="flex flex-wrap items-center gap-1.5 font-medium text-slate-900">
                            {fila.name}
                            {esYo && (
                                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                    Vos
                                </span>
                            )}
                            {fila.socio_validado && <SocioBadge compacta />}
                        </p>
                        <p className="truncate text-xs text-slate-500">
                            {fila.email}
                        </p>
                    </div>
                </div>
            </td>
            <td className="px-4 py-3">
                <span
                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ${rol?.chip ?? 'bg-slate-50 text-slate-600 ring-slate-200'}`}
                >
                    {rol?.texto ?? fila.rol}
                </span>
            </td>
            <td className="hidden px-4 py-3 text-right tabular-nums xl:table-cell">
                {fila.publicaciones_count}
            </td>
            <td className="hidden whitespace-nowrap px-4 py-3 text-slate-500 xl:table-cell">
                {formatoAlta.format(new Date(fila.created_at))}
            </td>
            <td className="px-4 py-3">
                {fila.inhabilitado ? (
                    <span className="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">
                        <Ban className="h-3 w-3" aria-hidden="true" />
                        Inhabilitado
                    </span>
                ) : (
                    <span className="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-200">
                        Activo
                    </span>
                )}
            </td>
            <td className="whitespace-nowrap px-4 py-3 text-right">
                {/* En la propia fila no hay acción: un admin no se inhabilita
                    ni se cambia el rol a sí mismo. */}
                {esYo ? (
                    <span className="text-xs text-slate-400">—</span>
                ) : (
                    <div className="flex justify-end gap-2">
                        {/* Dar o quitar admin (KAN-228). A un inhabilitado no se
                            le ofrece hacerlo admin. */}
                        {fila.rol === 'admin' ? (
                            <button
                                type="button"
                                onClick={onQuitarAdmin}
                                aria-label={`Quitar admin a ${fila.name}`}
                                title="Quitar admin"
                                disabled={procesando}
                                className={`${CLASE_ACCION} text-slate-600 ring-slate-200 hover:bg-slate-50`}
                            >
                                <ShieldMinus
                                    className="h-3.5 w-3.5"
                                    aria-hidden="true"
                                />
                                {/* En pantallas medianas, solo el ícono: así entran los dos botones. */}
                                <span className="hidden xl:inline">
                                    Quitar admin
                                </span>
                            </button>
                        ) : (
                            !fila.inhabilitado && (
                                <button
                                    type="button"
                                    onClick={onHacerAdmin}
                                    aria-label={`Hacer admin a ${fila.name}`}
                                    title="Hacer admin"
                                    disabled={procesando}
                                    className={`${CLASE_ACCION} text-violet-700 ring-violet-200 hover:bg-violet-50`}
                                >
                                    <ShieldPlus
                                        className="h-3.5 w-3.5"
                                        aria-hidden="true"
                                    />
                                    {/* En pantallas medianas, solo el ícono: así entran los dos botones. */}
                                    <span className="hidden xl:inline">
                                        Hacer admin
                                    </span>
                                </button>
                            )
                        )}
                        {fila.inhabilitado ? (
                            <button
                                type="button"
                                onClick={onReactivar}
                                disabled={procesando}
                                className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-green-700 ring-1 ring-green-200 transition-colors duration-150 hover:bg-green-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 disabled:opacity-50"
                            >
                                <RotateCcw
                                    className="h-3.5 w-3.5"
                                    aria-hidden="true"
                                />
                                Reactivar
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={onInhabilitar}
                                disabled={procesando}
                                className="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-red-600 ring-1 ring-red-200 transition-colors duration-150 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 disabled:opacity-50"
                            >
                                <Ban
                                    className="h-3.5 w-3.5"
                                    aria-hidden="true"
                                />
                                Inhabilitar
                            </button>
                        )}
                    </div>
                )}
            </td>
        </tr>
    );
}
