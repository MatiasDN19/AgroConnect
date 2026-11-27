import { ModalBloquear } from '@/Components/Admin/ModalesModeracion';
import DenunciarPublicacion, {
    MiDenuncia,
} from '@/Components/DenunciarPublicacion';
import Estrellas from '@/Components/Estrellas';
import GaleriaFotos from '@/Components/GaleriaFotos';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import ResenaForm from '@/Components/ResenaForm';
import SecondaryButton from '@/Components/SecondaryButton';
import SocioBadge from '@/Components/SocioBadge';
import SolicitarCompra, { MiSolicitud } from '@/Components/SolicitarCompra';
import WhatsappButton from '@/Components/WhatsappButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Categoria,
    PageProps,
    Publicacion,
    PublicacionFoto,
    Resena,
    User,
} from '@/types';
import { textoDistancia } from '@/utils/distancia';
import { formatearPrecio } from '@/utils/formato';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Ban, LockOpen, MapPin, ShieldAlert, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';

// Lo que manda PublicacionController::show() (con las relaciones cargadas).
type PublicacionDetalle = Publicacion & {
    categoria: Pick<Categoria, 'id' | 'nombre'>;
    usuario: Pick<
        User,
        'id' | 'name' | 'telefono' | 'socio_validado' | 'localidad'
    >;
    resenas: Resena[];
    fotos: PublicacionFoto[];
};

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const TEXTO_ESTADO: Record<string, string> = {
    pausada: 'Esta publicación está pausada por el productor.',
    vendida: 'Esta publicación ya fue vendida.',
};

export default function Show({
    publicacion,
    puntuacion_promedio,
    mi_denuncia,
    mi_solicitud,
    distancia_km,
}: PageProps<{
    publicacion: PublicacionDetalle;
    puntuacion_promedio: number | null;
    // Distancia entre el que mira y el productor (KAN-272). null si alguno de
    // los dos no tiene ubicación. Las coordenadas nunca llegan.
    distancia_km: number | null;
    // Denuncia del usuario logueado sobre esta publicación (KAN-65).
    mi_denuncia: MiDenuncia | null;
    // Última solicitud de compra del usuario logueado (KAN-116).
    mi_solicitud: MiSolicitud | null;
}>) {
    const { auth } = usePage().props;

    // Si se llega con #resenas (alerta de reseña nueva, KAN-344), bajar hasta
    // las reseñas: Inertia no hace scroll al ancla al navegar.
    useEffect(() => {
        if (window.location.hash !== '#resenas') return;
        const reducido = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        document
            .getElementById('resenas')
            ?.scrollIntoView({ behavior: reducido ? 'auto' : 'smooth' });
    }, []);

    // Misma regla que PublicacionPolicy::update. Es solo para mostrar el botón:
    // si alguien entra a /editar sin permiso, la ruta igual responde 403.
    const puedeEditar =
        auth.user.id === publicacion.usuario_id || auth.user.rol === 'admin';

    const avisoEstado = publicacion.estado
        ? TEXTO_ESTADO[publicacion.estado]
        : undefined;

    // Reseñas (KAN-59): el dueño no puede reseñar su publicación y cada usuario
    // deja una sola. Lo resolvemos acá para no mostrar un formulario que el
    // backend va a rechazar (StoreResenaRequest lo sigue validando igual).
    const esDueno = auth.user.id === publicacion.usuario_id;
    const yaReseno = publicacion.resenas.some(
        (resena) => resena.usuario.id === auth.user.id,
    );

    // Denuncias (KAN-65): el dueño no puede denunciar lo suyo (el backend lo rechaza)
    // y el admin no denuncia, modera desde su panel.
    const puedeDenunciar = !esDueno && auth.user.rol !== 'admin';

    // Solicitud de compra (KAN-116): misma regla, el dueño no compra lo suyo
    // (el backend lo rechaza) y el admin no compra.
    const puedeComprar = !esDueno && auth.user.rol !== 'admin';

    // Moderación (KAN-222): un admin puede bloquear la publicación. Bloqueada, el dueño
    // ve el motivo pero no puede cambiarle el estado (el backend lo rechaza).
    const esAdmin = auth.user.rol === 'admin';
    const bloqueada = publicacion.estado === 'bloqueada';
    const [bloqueando, setBloqueando] = useState(false);
    const [desbloqueando, setDesbloqueando] = useState(false);

    const desbloquear = () => {
        router.patch(
            route('admin.publicaciones.desbloquear', publicacion.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setDesbloqueando(true),
                onFinish: () => setDesbloqueando(false),
            },
        );
    };

    // Cambio de estado (KAN-47): PATCH /publicaciones/{id}/estado.
    const [procesando, setProcesando] = useState(false);
    const [confirmandoVendida, setConfirmandoVendida] = useState(false);

    const cambiarEstado = (estado: NonNullable<Publicacion['estado']>) => {
        router.patch(
            route('publicaciones.estado', publicacion.id),
            { estado },
            {
                preserveScroll: true,
                onStart: () => setProcesando(true),
                onFinish: () => {
                    setProcesando(false);
                    setConfirmandoVendida(false);
                },
            },
        );
    };

    // Bloqueada y quien mira no es el dueño ni un admin: no se muestra nada de la
    // publicación (el listado ya la oculta, pero se puede llegar por un link viejo).
    if (bloqueada && !puedeEditar) {
        return (
            <AuthenticatedLayout>
                <Head title="Publicación no disponible" />
                <div className="py-12">
                    <div className="mx-auto max-w-xl px-4 sm:px-6">
                        <div className="space-y-3 rounded-lg bg-white p-8 text-center shadow-sm">
                            <ShieldAlert
                                className="mx-auto h-10 w-10 text-slate-400"
                                aria-hidden="true"
                            />
                            <h1 className="text-lg font-semibold text-gray-900">
                                Esta publicación no está disponible
                            </h1>
                            <p className="text-sm text-gray-600">
                                Puede que el productor la haya retirado o que
                                esté en revisión.
                            </p>
                            <Link
                                href={route('publicaciones.index')}
                                className="inline-block text-sm font-semibold text-marca-azul hover:underline"
                            >
                                Ver otras publicaciones
                            </Link>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {publicacion.titulo}
                </h2>
            }
        >
            <Head title={publicacion.titulo} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    {bloqueada && (
                        <div
                            role="status"
                            className="flex gap-3 rounded-md bg-red-50 p-4 text-sm text-red-900"
                        >
                            <ShieldAlert
                                className="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                                aria-hidden="true"
                            />
                            <div className="space-y-1">
                                <p className="font-semibold">
                                    Un administrador bloqueó esta publicación
                                    {publicacion.bloqueada_at &&
                                        ` el ${formatoFecha.format(new Date(publicacion.bloqueada_at))}`}
                                    .
                                </p>
                                {publicacion.motivo_bloqueo && (
                                    <p>
                                        <span className="font-medium">
                                            Motivo:
                                        </span>{' '}
                                        {publicacion.motivo_bloqueo}
                                    </p>
                                )}
                                <p className="text-red-800/80">
                                    {esAdmin
                                        ? 'No aparece en el marketplace. Si se resolvió, podés desbloquearla: queda pausada y el productor decide cuándo reactivarla.'
                                        : 'No aparece en el marketplace. Si creés que es un error, comunicate con el Consorcio.'}
                                </p>
                            </div>
                        </div>
                    )}

                    {avisoEstado && (
                        <div className="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                            {avisoEstado}
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Datos de la publicación */}
                        <section className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg lg:col-span-2">
                            <GaleriaFotos
                                fotos={publicacion.fotos ?? []}
                                titulo={publicacion.titulo}
                                categoria={publicacion.categoria.nombre}
                            />

                            <span className="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                                {publicacion.categoria.nombre}
                            </span>

                            <div className="flex flex-wrap items-baseline gap-x-2">
                                <p className="text-3xl font-semibold text-gray-900">
                                    {formatearPrecio(publicacion.precio)}
                                </p>
                                <p className="text-gray-600">
                                    por {publicacion.unidad}
                                </p>
                            </div>

                            <p className="text-sm text-gray-600">
                                {publicacion.stock > 0 ? (
                                    <>
                                        Stock disponible:{' '}
                                        <strong className="text-gray-900">
                                            {publicacion.stock.toLocaleString(
                                                'es-AR',
                                            )}{' '}
                                            {publicacion.unidad}
                                        </strong>
                                    </>
                                ) : (
                                    <strong className="text-red-700">
                                        Sin stock disponible
                                    </strong>
                                )}
                            </p>

                            <div>
                                <h3 className="text-sm font-medium text-gray-900">
                                    Descripción
                                </h3>
                                {/* whitespace-pre-line respeta los saltos de línea que escribió el productor */}
                                <p className="mt-1 whitespace-pre-line text-gray-700">
                                    {publicacion.descripcion}
                                </p>
                            </div>

                            {puedeEditar && (
                                // Acciones del dueño (o admin): editar y cambiar estado.
                                // Los botones de estado dependen del estado actual:
                                //   activa  -> Pausar, Marcar vendida
                                //   pausada -> Reactivar, Marcar vendida
                                //   vendida -> Reactivar
                                <div className="flex flex-wrap gap-3 border-t border-gray-100 pt-4">
                                    {/* Bloqueada: el estado solo lo cambia un admin desde la moderación. */}
                                    {!bloqueada &&
                                        publicacion.estado !== 'activa' && (
                                            <PrimaryButton
                                                onClick={() =>
                                                    cambiarEstado('activa')
                                                }
                                                disabled={procesando}
                                            >
                                                Reactivar
                                            </PrimaryButton>
                                        )}

                                    {!bloqueada &&
                                        publicacion.estado === 'activa' && (
                                            <SecondaryButton
                                                onClick={() =>
                                                    cambiarEstado('pausada')
                                                }
                                                disabled={procesando}
                                            >
                                                Pausar
                                            </SecondaryButton>
                                        )}

                                    {!bloqueada &&
                                        publicacion.estado !== 'vendida' && (
                                            <SecondaryButton
                                                onClick={() =>
                                                    setConfirmandoVendida(true)
                                                }
                                                disabled={procesando}
                                            >
                                                Marcar como vendida
                                            </SecondaryButton>
                                        )}

                                    <Link
                                        href={route(
                                            'publicaciones.edit',
                                            publicacion.id,
                                        )}
                                        className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Editar
                                    </Link>
                                </div>
                            )}

                            {esAdmin && (
                                // Moderación (KAN-222). Eliminar queda solo en el panel: el backend
                                // vuelve a la página anterior y desde acá ya no existiría.
                                <div className="flex flex-wrap items-center gap-3 rounded-lg bg-slate-50 p-3">
                                    <span className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <ShieldCheck
                                            className="h-4 w-4"
                                            aria-hidden="true"
                                        />
                                        Moderación
                                    </span>
                                    {bloqueada ? (
                                        <button
                                            type="button"
                                            onClick={desbloquear}
                                            disabled={desbloqueando}
                                            className="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-green-700 ring-1 ring-green-300 transition hover:bg-green-50 disabled:opacity-50"
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
                                            onClick={() => setBloqueando(true)}
                                            className="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-red-700 ring-1 ring-red-300 transition hover:bg-red-50"
                                        >
                                            <Ban
                                                className="h-3.5 w-3.5"
                                                aria-hidden="true"
                                            />
                                            Bloquear
                                        </button>
                                    )}
                                    <Link
                                        href={route(
                                            'admin.publicaciones.index',
                                            {
                                                q: publicacion.titulo,
                                            },
                                        )}
                                        className="ms-auto text-xs font-medium text-marca-azul hover:underline"
                                    >
                                        Moderar en el panel →
                                    </Link>
                                </div>
                            )}
                        </section>

                        {/* Contacto del productor */}
                        <aside className="h-fit space-y-2 bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="text-sm font-medium text-gray-500">
                                Productor
                            </h3>
                            <p className="text-lg font-semibold text-gray-900">
                                {publicacion.usuario.name}
                            </p>
                            {publicacion.usuario.socio_validado && (
                                <SocioBadge />
                            )}
                            <UbicacionProductor
                                localidad={publicacion.usuario.localidad}
                                distanciaKm={esDueno ? null : distancia_km}
                            />

                            {publicacion.usuario.telefono ? (
                                <a
                                    href={`tel:${publicacion.usuario.telefono}`}
                                    className="block text-indigo-600 hover:underline"
                                >
                                    {publicacion.usuario.telefono}
                                </a>
                            ) : (
                                <p className="text-sm text-gray-500">
                                    No cargó un teléfono de contacto.
                                </p>
                            )}
                            {/* WhatsApp (KAN-178): no aparece si el teléfono no se puede
                                convertir a un número válido; queda el teléfono de arriba. */}
                            {!esDueno && (
                                <WhatsappButton
                                    telefono={publicacion.usuario.telefono}
                                    mensaje={`¡Hola ${publicacion.usuario.name}! Vi tu publicación "${publicacion.titulo}" en AgroConnect y quería hacerte una consulta.`}
                                    className="w-full"
                                />
                            )}

                            {/* Con la publicación pausada o vendida no se puede pedir,
                                pero si ya había una solicitud se sigue viendo en qué quedó. */}
                            {puedeComprar &&
                                (publicacion.estado === 'activa' ||
                                    mi_solicitud) && (
                                    <div className="pt-3">
                                        <SolicitarCompra
                                            publicacion={publicacion}
                                            miSolicitud={mi_solicitud}
                                            disponible={
                                                publicacion.estado === 'activa'
                                            }
                                        />
                                    </div>
                                )}

                            {puedeDenunciar && (
                                <div className="border-t border-gray-100 pt-3">
                                    <DenunciarPublicacion
                                        publicacionId={publicacion.id}
                                        miDenuncia={mi_denuncia}
                                    />
                                </div>
                            )}
                        </aside>
                    </div>

                    {/* Reseñas. El id lo usa la alerta de reseña nueva (KAN-344) para
                        bajar directo hasta acá; scroll-mt deja lugar al navbar fijo. */}
                    <section
                        id="resenas"
                        className="scroll-mt-24 bg-white p-6 shadow-sm sm:rounded-lg"
                    >
                        <div className="flex flex-wrap items-center gap-3">
                            <h3 className="text-lg font-medium text-gray-900">
                                Reseñas
                            </h3>

                            {puntuacion_promedio !== null && (
                                <div className="flex items-center gap-2 text-sm text-gray-600">
                                    <Estrellas valor={puntuacion_promedio} />
                                    <span>
                                        {puntuacion_promedio.toFixed(1)} de 5 (
                                        {publicacion.resenas.length})
                                    </span>
                                </div>
                            )}
                        </div>

                        {publicacion.resenas.length === 0 ? (
                            <p className="mt-4 text-sm text-gray-600">
                                Todavía no hay reseñas para esta publicación.
                            </p>
                        ) : (
                            <ul className="mt-4 divide-y divide-gray-100">
                                {publicacion.resenas.map((resena) => (
                                    <li key={resena.id} className="py-4">
                                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <Estrellas
                                                valor={resena.puntuacion}
                                            />
                                            <span className="text-sm font-medium text-gray-900">
                                                {resena.usuario.name}
                                            </span>
                                            <span className="text-xs text-gray-500">
                                                {formatoFecha.format(
                                                    new Date(resena.created_at),
                                                )}
                                            </span>
                                        </div>
                                        {resena.comentario && (
                                            <p className="mt-2 whitespace-pre-line text-sm text-gray-700">
                                                {resena.comentario}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                        {!esDueno && (
                            <div className="mt-6 border-t border-gray-100 pt-6">
                                {yaReseno ? (
                                    <p className="text-sm text-gray-600">
                                        Ya dejaste tu reseña en esta
                                        publicación. ¡Gracias por compartir tu
                                        experiencia!
                                    </p>
                                ) : (
                                    <ResenaForm
                                        publicacionId={publicacion.id}
                                    />
                                )}
                            </div>
                        )}
                    </section>
                </div>
            </div>

            <Modal
                show={confirmandoVendida}
                onClose={() => setConfirmandoVendida(false)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        ¿Marcar “{publicacion.titulo}” como vendida?
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        Va a dejar de aparecer en el listado de publicaciones
                        activas. Si te equivocaste, la podés reactivar después.
                    </p>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton
                            onClick={() => setConfirmandoVendida(false)}
                        >
                            Cancelar
                        </SecondaryButton>

                        <PrimaryButton
                            className="ms-3"
                            onClick={() => cambiarEstado('vendida')}
                            disabled={procesando}
                        >
                            Sí, marcar como vendida
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>
            <ModalBloquear
                publicacion={bloqueando ? publicacion : null}
                onCerrar={() => setBloqueando(false)}
            />
        </AuthenticatedLayout>
    );
}

// "📍 Laguna Naineck · a 12 km" debajo del nombre del productor (KAN-274).
// Muestra lo que haya: solo la localidad, solo la distancia o nada.
function UbicacionProductor({
    localidad,
    distanciaKm,
}: {
    localidad?: string | null;
    distanciaKm: number | null;
}) {
    const partes = [
        localidad?.trim() || null,
        distanciaKm == null ? null : textoDistancia(Number(distanciaKm)),
    ].filter(Boolean);

    if (partes.length === 0) return null;

    const texto = partes.join(' · ');

    return (
        <p className="flex items-center gap-1.5 text-sm text-gray-600">
            <MapPin
                className="h-4 w-4 shrink-0 text-marca-azul"
                aria-hidden="true"
            />
            {/* Si solo está la distancia, arranca con mayúscula: "A 12 km". */}
            {texto.replace(/^a /, 'A ')}
        </p>
    );
}
