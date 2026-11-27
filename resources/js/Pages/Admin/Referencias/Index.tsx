import { avisar } from '@/Components/Avisos/avisos';
import {
    BotonGuardar,
    BotonPeligro,
    BotonSecundario,
    CLASES_CAMPO,
} from '@/Components/Formulario';
import Modal from '@/Components/Modal';
import { estiloCategoria } from '@/Components/PlaceholderCategoria';
import AdminLayout from '@/Layouts/AdminLayout';
import { Categoria } from '@/types';
import { formatearPrecio } from '@/utils/formato';
import { UNIDADES_SUGERIDAS } from '@/utils/unidades';
import { router, useForm } from '@inertiajs/react';
import {
    Check,
    ChevronLeft,
    ChevronRight,
    Copy,
    Info,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-react';
import { FormEventHandler, KeyboardEvent, ReactNode, useState } from 'react';

// "Referencias del mes" del panel admin (KAN-339). Backend: KAN-337
// (ReferenciaMensualController). Cada fila es un producto del mes con su
// precio de referencia y/o la cantidad que pide el Plan Nutrir. Se edita en la
// misma fila, de a una; en el celu cada fila se ve como tarjeta.

interface Referencia {
    id: number;
    mes: string;
    producto: string;
    categoria_id: number;
    unidad: string;
    precio_referencia: string | null;
    cantidad_plan_nutrir: string | null;
    nota: string | null;
    categoria?: Pick<Categoria, 'id' | 'nombre'>;
}

type Editando = number | 'nueva' | null;

const formatoMes = new Intl.DateTimeFormat('es-AR', {
    month: 'long',
    year: 'numeric',
});
const formatoCantidad = new Intl.NumberFormat('es-AR', {
    maximumFractionDigits: 2,
});

// "2026-11-01" -> Date al mediodía (así la zona horaria no lo corre de día).
const aFecha = (mes: string) => new Date(`${mes.slice(0, 7)}-01T12:00:00`);
const textoMes = (mes: string) => {
    const texto = formatoMes.format(aFecha(mes));
    return texto.charAt(0).toUpperCase() + texto.slice(1);
};
const mesVecino = (mes: string, salto: number) => {
    const fecha = aFecha(mes);
    fecha.setMonth(fecha.getMonth() + salto);
    return `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
};

// Columnas de la grilla en compu (en el celu cada fila es una tarjeta).
const COLUMNAS =
    'md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_minmax(0,0.9fr)_minmax(0,1.1fr)_minmax(0,1.1fr)_5.5rem] md:items-center md:gap-3';

export default function Index({
    referencias,
    categorias,
    mes,
}: {
    referencias: Referencia[];
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
    mes: string;
}) {
    const [editando, setEditando] = useState<Editando>(null);
    const [aBorrar, setABorrar] = useState<Referencia | null>(null);
    const [copiando, setCopiando] = useState(false);

    const formulario = useForm({
        mes,
        producto: '',
        categoria_id: '' as number | '',
        unidad: 'kg',
        precio_referencia: '',
        cantidad_plan_nutrir: '',
        nota: '',
    });

    const empezar = (referencia: Referencia | null) => {
        formulario.clearErrors();
        formulario.setData({
            mes,
            producto: referencia?.producto ?? '',
            categoria_id: referencia?.categoria_id ?? '',
            unidad: referencia?.unidad ?? 'kg',
            precio_referencia: referencia?.precio_referencia ?? '',
            cantidad_plan_nutrir: referencia?.cantidad_plan_nutrir ?? '',
            nota: referencia?.nota ?? '',
        });
        setEditando(referencia ? referencia.id : 'nueva');
    };

    const cancelar = () => {
        formulario.clearErrors();
        setEditando(null);
    };

    const guardar: FormEventHandler = (e) => {
        e.preventDefault();
        const opciones = {
            preserveScroll: true,
            onSuccess: () => setEditando(null),
        };
        if (editando === 'nueva') {
            formulario.post(route('admin.referencias.store'), opciones);
        } else if (editando !== null) {
            formulario.put(
                route('admin.referencias.update', editando),
                opciones,
            );
        }
    };

    const irAlMes = (destino: string) => {
        setEditando(null);
        router.get(
            route('admin.referencias.index'),
            { mes: destino },
            { preserveScroll: true },
        );
    };

    const copiarMesAnterior = () => {
        setCopiando(true);
        router.post(
            route('admin.referencias.copiar-mes-anterior'),
            { mes },
            {
                preserveScroll: true,
                onSuccess: (pagina) => {
                    // El backend avisa con un mensaje "info" que no llega al
                    // frontend: si el mes sigue vacío, avisamos acá.
                    const filas = pagina.props.referencias as Referencia[];
                    if (filas.length === 0) {
                        avisar(
                            'info',
                            'No hay referencias en el mes anterior para copiar.',
                        );
                    }
                },
                onFinish: () => setCopiando(false),
            },
        );
    };

    const borrar = () => {
        if (!aBorrar) return;
        router.delete(route('admin.referencias.destroy', aBorrar.id), {
            preserveScroll: true,
            onFinish: () => setABorrar(null),
        });
    };

    const conPlanNutrir = referencias.filter(
        (r) => r.cantidad_plan_nutrir !== null,
    ).length;
    const filaFormulario = (
        <FilaEditable
            formulario={formulario}
            categorias={categorias}
            guardar={guardar}
            cancelar={cancelar}
        />
    );

    return (
        <AdminLayout
            titulo="Referencias del mes"
            acciones={
                <BotonGuardar
                    type="button"
                    onClick={() => empezar(null)}
                    disabled={editando === 'nueva'}
                    className="inline-flex items-center gap-1.5"
                >
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Agregar producto
                </BotonGuardar>
            }
        >
            <div className="mx-auto max-w-6xl space-y-5">
                <p className="flex gap-2.5 rounded-xl bg-sky-50 p-4 text-sm text-sky-900 ring-1 ring-sky-100">
                    <Info
                        className="mt-0.5 h-4 w-4 shrink-0 text-marca-azul"
                        aria-hidden="true"
                    />
                    <span>
                        Los precios de referencia los define cada mes la mesa
                        administrativa del Consorcio, y la cantidad la pide el
                        Plan Nutrir. Acá solo se cargan: los productores los ven
                        al publicar y en el Inicio.
                    </span>
                </p>

                {/* Selector de mes */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-1">
                        <BotonMes
                            etiqueta="Mes anterior"
                            onClick={() => irAlMes(mesVecino(mes, -1))}
                        >
                            <ChevronLeft
                                className="h-5 w-5"
                                aria-hidden="true"
                            />
                        </BotonMes>
                        <h2
                            className="min-w-[11rem] text-center font-display text-xl font-bold text-marca-tinta"
                            aria-live="polite"
                        >
                            {textoMes(mes)}
                        </h2>
                        <BotonMes
                            etiqueta="Mes siguiente"
                            onClick={() => irAlMes(mesVecino(mes, 1))}
                        >
                            <ChevronRight
                                className="h-5 w-5"
                                aria-hidden="true"
                            />
                        </BotonMes>
                    </div>
                    {referencias.length > 0 && (
                        <p className="text-sm text-slate-600">
                            {referencias.length}{' '}
                            {referencias.length === 1
                                ? 'producto'
                                : 'productos'}
                            {conPlanNutrir > 0 &&
                                ` · ${conPlanNutrir} con pedido del Plan Nutrir`}
                        </p>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                    {/* Encabezado de la grilla (solo compu) */}
                    <div
                        aria-hidden="true"
                        className={`hidden border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 ${COLUMNAS}`}
                    >
                        <span>Producto</span>
                        <span>Categoría</span>
                        <span>Unidad</span>
                        <span className="text-right">Precio</span>
                        <span className="text-right">Plan Nutrir</span>
                        <span />
                    </div>

                    {referencias.length === 0 && editando !== 'nueva' ? (
                        <div className="flex flex-col items-center gap-3 px-6 py-12 text-center">
                            <p className="font-medium text-slate-700">
                                Todavía no hay referencias para{' '}
                                {textoMes(mes).toLowerCase()}.
                            </p>
                            <p className="max-w-md text-sm text-slate-500">
                                Podés copiar la lista del mes anterior y ajustar
                                los precios, o cargar los productos de a uno.
                            </p>
                            <div className="mt-2 flex flex-wrap justify-center gap-3">
                                <BotonSecundario
                                    onClick={copiarMesAnterior}
                                    disabled={copiando}
                                    className="inline-flex items-center gap-2"
                                >
                                    <Copy
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                    Copiar del mes anterior
                                </BotonSecundario>
                                <BotonGuardar
                                    type="button"
                                    onClick={() => empezar(null)}
                                    className="inline-flex items-center gap-1.5"
                                >
                                    <Plus
                                        className="h-4 w-4"
                                        aria-hidden="true"
                                    />
                                    Agregar producto
                                </BotonGuardar>
                            </div>
                        </div>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {editando === 'nueva' && (
                                <li className="bg-sky-50/40">
                                    {filaFormulario}
                                </li>
                            )}
                            {referencias.map((referencia) => (
                                <li
                                    key={referencia.id}
                                    className={
                                        editando === referencia.id
                                            ? 'bg-sky-50/40'
                                            : undefined
                                    }
                                >
                                    {editando === referencia.id ? (
                                        filaFormulario
                                    ) : (
                                        <FilaLectura
                                            referencia={referencia}
                                            editar={() => empezar(referencia)}
                                            borrar={() =>
                                                setABorrar(referencia)
                                            }
                                            bloqueada={editando !== null}
                                        />
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <Modal show={aBorrar !== null} onClose={() => setABorrar(null)}>
                <div className="p-6">
                    <h2 className="font-display text-lg font-bold text-marca-tinta">
                        ¿Borrar {aBorrar?.producto}?
                    </h2>
                    <p className="mt-1 text-sm text-slate-600">
                        Se saca de las referencias de {textoMes(mes)}. Los
                        productores dejan de verla al publicar.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <BotonSecundario onClick={() => setABorrar(null)}>
                            Cancelar
                        </BotonSecundario>
                        <BotonPeligro onClick={borrar}>Borrar</BotonPeligro>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

function BotonMes({
    etiqueta,
    onClick,
    children,
}: {
    etiqueta: string;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={etiqueta}
            title={etiqueta}
            className="rounded-lg p-2 text-slate-500 transition-colors duration-150 hover:bg-white hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 active:scale-95"
        >
            {children}
        </button>
    );
}

function FilaLectura({
    referencia,
    editar,
    borrar,
    bloqueada,
}: {
    referencia: Referencia;
    editar: () => void;
    borrar: () => void;
    bloqueada: boolean;
}) {
    const nombreCategoria = referencia.categoria?.nombre ?? '';
    const { Icono, fondo } = estiloCategoria(nombreCategoria);

    return (
        <div className={`px-4 py-3.5 ${COLUMNAS}`}>
            <div className="flex items-start justify-between gap-3 md:block">
                <div className="min-w-0">
                    <p className="font-medium text-marca-tinta">
                        {referencia.producto}
                    </p>
                    {referencia.nota && (
                        <p className="mt-0.5 text-xs text-slate-500">
                            {referencia.nota}
                        </p>
                    )}
                </div>
                {/* Acciones arriba a la derecha en la tarjeta del celu */}
                <Acciones
                    className="md:hidden"
                    {...{ referencia, editar, borrar, bloqueada }}
                />
            </div>

            <p className="mt-2 flex items-center gap-2 text-sm text-slate-600 md:mt-0">
                <span
                    className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${fondo}`}
                >
                    <Icono className="h-4 w-4" aria-hidden="true" />
                </span>
                {nombreCategoria}
                <span className="text-slate-400 md:hidden">
                    · {referencia.unidad}
                </span>
            </p>

            <p className="hidden text-sm text-slate-600 md:block">
                {referencia.unidad}
            </p>

            <p className="mt-2 flex justify-between text-sm md:mt-0 md:block md:text-right">
                <span className="text-slate-500 md:hidden">
                    Precio de referencia
                </span>
                {referencia.precio_referencia !== null ? (
                    <span className="font-semibold text-marca-tinta">
                        {formatearPrecio(referencia.precio_referencia)}
                        <span className="font-normal text-slate-500">
                            {' '}
                            / {referencia.unidad}
                        </span>
                    </span>
                ) : (
                    <span className="text-slate-400">—</span>
                )}
            </p>

            <p className="mt-1 flex justify-between text-sm md:mt-0 md:block md:text-right">
                <span className="text-slate-500 md:hidden">Plan Nutrir</span>
                {referencia.cantidad_plan_nutrir !== null ? (
                    <span className="font-medium text-green-700">
                        {formatoCantidad.format(
                            Number(referencia.cantidad_plan_nutrir),
                        )}{' '}
                        {referencia.unidad}
                    </span>
                ) : (
                    <span className="text-slate-400">—</span>
                )}
            </p>

            <Acciones
                className="hidden md:flex"
                {...{ referencia, editar, borrar, bloqueada }}
            />
        </div>
    );
}

function Acciones({
    referencia,
    editar,
    borrar,
    bloqueada,
    className,
}: {
    referencia: Referencia;
    editar: () => void;
    borrar: () => void;
    bloqueada: boolean;
    className: string;
}) {
    const clases =
        'rounded-lg p-2 text-slate-400 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 disabled:pointer-events-none disabled:opacity-40';

    return (
        <div className={`shrink-0 justify-end gap-1 ${className}`}>
            <button
                type="button"
                onClick={editar}
                disabled={bloqueada}
                aria-label={`Editar ${referencia.producto}`}
                title="Editar"
                className={`${clases} hover:bg-slate-100 hover:text-slate-700`}
            >
                <Pencil className="h-4 w-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                onClick={borrar}
                disabled={bloqueada}
                aria-label={`Borrar ${referencia.producto}`}
                title="Borrar"
                className={`${clases} hover:bg-red-50 hover:text-red-600`}
            >
                <Trash2 className="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    );
}

type FormularioReferencia = ReturnType<
    typeof useForm<{
        mes: string;
        producto: string;
        categoria_id: number | '';
        unidad: string;
        precio_referencia: string;
        cantidad_plan_nutrir: string;
        nota: string;
    }>
>;

// La misma fila convertida en campos. Enter guarda y Escape cancela.
function FilaEditable({
    formulario,
    categorias,
    guardar,
    cancelar,
}: {
    formulario: FormularioReferencia;
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
    guardar: FormEventHandler;
    cancelar: () => void;
}) {
    const { data, setData, errors, processing } = formulario;
    const errores = Object.values(errors);
    const alApretar = (e: KeyboardEvent) => {
        if (e.key === 'Escape') cancelar();
    };

    return (
        <form onSubmit={guardar} onKeyDown={alApretar} className="px-4 py-3.5">
            <div className={`space-y-3 md:space-y-0 ${COLUMNAS}`}>
                <CampoFila etiqueta="Producto" id="ref-producto">
                    <input
                        id="ref-producto"
                        value={data.producto}
                        onChange={(e) => setData('producto', e.target.value)}
                        placeholder="Ej: Zapallo"
                        maxLength={255}
                        required
                        autoFocus
                        className={`${CLASES_CAMPO} md:mt-0`}
                    />
                </CampoFila>

                <CampoFila etiqueta="Categoría" id="ref-categoria">
                    <select
                        id="ref-categoria"
                        value={data.categoria_id}
                        onChange={(e) =>
                            setData(
                                'categoria_id',
                                e.target.value ? Number(e.target.value) : '',
                            )
                        }
                        required
                        className={`${CLASES_CAMPO} md:mt-0`}
                    >
                        <option value="">Elegí…</option>
                        {categorias.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.nombre}
                            </option>
                        ))}
                    </select>
                </CampoFila>

                <CampoFila etiqueta="Unidad" id="ref-unidad">
                    <input
                        id="ref-unidad"
                        list="ref-unidades"
                        value={data.unidad}
                        onChange={(e) => setData('unidad', e.target.value)}
                        maxLength={50}
                        required
                        className={`${CLASES_CAMPO} md:mt-0`}
                    />
                    <datalist id="ref-unidades">
                        {UNIDADES_SUGERIDAS.map((u) => (
                            <option key={u} value={u} />
                        ))}
                    </datalist>
                </CampoFila>

                <CampoFila etiqueta="Precio de referencia ($)" id="ref-precio">
                    <input
                        id="ref-precio"
                        type="number"
                        inputMode="decimal"
                        min="0"
                        step="0.01"
                        value={data.precio_referencia}
                        onChange={(e) =>
                            setData('precio_referencia', e.target.value)
                        }
                        placeholder="—"
                        className={`${CLASES_CAMPO} md:mt-0 md:text-right`}
                    />
                </CampoFila>

                <CampoFila etiqueta="Cantidad Plan Nutrir" id="ref-cantidad">
                    <input
                        id="ref-cantidad"
                        type="number"
                        inputMode="decimal"
                        min="0"
                        step="0.01"
                        value={data.cantidad_plan_nutrir}
                        onChange={(e) =>
                            setData('cantidad_plan_nutrir', e.target.value)
                        }
                        placeholder="—"
                        className={`${CLASES_CAMPO} md:mt-0 md:text-right`}
                    />
                </CampoFila>

                <div className="flex justify-end gap-1 pt-1 md:pt-0">
                    <button
                        type="submit"
                        disabled={processing}
                        aria-label="Guardar"
                        title="Guardar (Enter)"
                        className="rounded-lg bg-marca-azul-profundo p-2 text-white transition hover:bg-marca-azul focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 disabled:opacity-60"
                    >
                        <Check className="h-4 w-4" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        onClick={cancelar}
                        aria-label="Cancelar"
                        title="Cancelar (Escape)"
                        className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600"
                    >
                        <X className="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <div className="mt-3">
                <label htmlFor="ref-nota" className="sr-only">
                    Nota
                </label>
                <input
                    id="ref-nota"
                    value={data.nota}
                    onChange={(e) => setData('nota', e.target.value)}
                    maxLength={255}
                    placeholder="Nota (opcional), ej: solo zapallo anco"
                    className={CLASES_CAMPO}
                />
            </div>

            {errores.length > 0 && (
                <ul
                    role="alert"
                    className="mt-3 space-y-0.5 rounded-lg bg-red-50 p-3 text-sm text-red-700"
                >
                    {errores.map((mensaje) => (
                        <li key={mensaje}>{mensaje}</li>
                    ))}
                </ul>
            )}
        </form>
    );
}

// En el celu la etiqueta se ve arriba del campo; en compu la da el encabezado
// de la grilla, así que queda solo para lectores de pantalla.
function CampoFila({
    etiqueta,
    id,
    children,
}: {
    etiqueta: string;
    id: string;
    children: ReactNode;
}) {
    return (
        <div>
            <label
                htmlFor={id}
                className="block text-xs font-medium text-slate-600 md:sr-only"
            >
                {etiqueta}
            </label>
            {children}
        </div>
    );
}
