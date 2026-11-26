import Avatar from '@/Components/Avatar';
import SocioBadge from '@/Components/SocioBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, User } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    BadgeCheck,
    LucideIcon,
    MapPin,
    ShieldCheck,
    Trash2,
    UserRound,
} from 'lucide-react';
import { KeyboardEvent, ReactNode, useEffect, useRef, useState } from 'react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import UpdateSocioForm from './Partials/UpdateSocioForm';
import UpdateUbicacionForm from './Partials/UpdateUbicacionForm';

// Perfil rediseñado (KAN-322): cabecera con los datos del usuario y una
// sección a la vez, elegida desde un menú lateral (compu) o pestañas (celu).
// La sección queda en la URL (#seguridad) para poder volver o compartirla.
// Para sumar una sección (ej. Notificaciones, KAN-77) alcanza con agregar una
// entrada a "secciones".

const ROLES: Record<User['rol'], string> = {
    productor: 'Productor',
    comprador: 'Comprador',
    admin: 'Administrador',
};

const formatoAlta = new Intl.DateTimeFormat('es-AR', {
    month: 'long',
    year: 'numeric',
});

interface Seccion {
    id: string;
    etiqueta: string;
    Icono: LucideIcon;
    peligro?: boolean;
    contenido: ReactNode;
}

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    const user = usePage().props.auth.user;
    const esProductor = user.rol === 'productor';

    const secciones: Seccion[] = [
        {
            id: 'datos',
            etiqueta: 'Datos de la cuenta',
            Icono: UserRound,
            contenido: (
                <UpdateProfileInformationForm
                    mustVerifyEmail={mustVerifyEmail}
                    status={status}
                />
            ),
        },
        // Ubicación (KAN-273): productores (su chacra) y compradores (cercanía).
        ...(user.rol !== 'admin'
            ? [
                  {
                      id: 'ubicacion',
                      etiqueta: 'Ubicación',
                      Icono: MapPin,
                      contenido: <UpdateUbicacionForm />,
                  },
              ]
            : []),
        // Solo productores: la validación de socio prioriza sus publicaciones.
        ...(esProductor
            ? [
                  {
                      id: 'socio',
                      etiqueta: 'Socio del Consorcio',
                      Icono: BadgeCheck,
                      contenido: <UpdateSocioForm />,
                  },
              ]
            : []),
        {
            id: 'seguridad',
            etiqueta: 'Seguridad',
            Icono: ShieldCheck,
            contenido: <UpdatePasswordForm />,
        },
        {
            id: 'eliminar',
            etiqueta: 'Eliminar cuenta',
            Icono: Trash2,
            peligro: true,
            contenido: <DeleteUserForm />,
        },
    ];

    const activa = useSeccionActiva(secciones.map((s) => s.id));
    const seccion = secciones.find((s) => s.id === activa.id) ?? secciones[0];

    return (
        // Sin buscador en el navbar: acá no se buscan productos.
        <AuthenticatedLayout mostrarBuscador={false}>
            <Head title="Mi perfil" />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <Cabecera user={user} />

                <div className="mt-8 lg:grid lg:grid-cols-[15rem_1fr] lg:gap-8">
                    <Navegacion
                        secciones={secciones}
                        activa={seccion.id}
                        elegir={activa.elegir}
                    />

                    <div
                        id={`panel-${seccion.id}`}
                        role="tabpanel"
                        aria-labelledby={`pestana-${seccion.id}`}
                        className="mt-5 min-w-0 lg:mt-0"
                    >
                        {seccion.contenido}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Cabecera({ user }: { user: User }) {
    return (
        <div className="flex items-center gap-4 sm:gap-5">
            <Avatar nombre={user.name} tamano="grande" />
            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                    <h1 className="break-words font-display text-2xl font-bold leading-tight text-marca-tinta sm:text-3xl">
                        {user.name}
                    </h1>
                    {user.socio_validado && <SocioBadge />}
                </div>
                <p className="mt-1 text-sm text-slate-500">
                    <span className="font-medium text-slate-700">
                        {ROLES[user.rol]}
                    </span>
                    {user.created_at && (
                        <>
                            {' · '}En AgroConnect desde{' '}
                            {formatoAlta.format(new Date(user.created_at))}
                        </>
                    )}
                </p>
            </div>
        </div>
    );
}

// Menú lateral en compu y fila de pestañas deslizable en el celu. Es la misma
// lista con dos acomodos, así el teclado y los lectores de pantalla la
// recorren igual (flechas para moverse entre pestañas).
function Navegacion({
    secciones,
    activa,
    elegir,
}: {
    secciones: Seccion[];
    activa: string;
    elegir: (id: string) => void;
}) {
    const botones = useRef<(HTMLButtonElement | null)[]>([]);
    const fila = useRef<HTMLDivElement>(null);

    // En el celu la fila se desliza: si la pestaña elegida quedó cortada
    // (ej. se entró con #eliminar), la traemos a la vista sin mover la página.
    useEffect(() => {
        const contenedor = fila.current;
        const boton =
            botones.current[secciones.findIndex((s) => s.id === activa)];
        if (!contenedor || !boton) return;
        const izquierda = boton.offsetLeft - contenedor.offsetLeft;
        const derecha = izquierda + boton.offsetWidth;
        if (izquierda < contenedor.scrollLeft) {
            contenedor.scrollLeft = izquierda - 16;
        } else if (derecha > contenedor.scrollLeft + contenedor.clientWidth) {
            contenedor.scrollLeft = derecha - contenedor.clientWidth + 16;
        }
    }, [activa, secciones]);

    const alApretar = (e: KeyboardEvent, indice: number) => {
        const siguiente = ['ArrowDown', 'ArrowRight'].includes(e.key)
            ? 1
            : ['ArrowUp', 'ArrowLeft'].includes(e.key)
              ? -1
              : 0;
        if (!siguiente) return;
        e.preventDefault();
        const destino =
            (indice + siguiente + secciones.length) % secciones.length;
        elegir(secciones[destino].id);
        botones.current[destino]?.focus();
    };

    return (
        <nav
            aria-label="Secciones del perfil"
            className="lg:sticky lg:top-24 lg:self-start"
        >
            <div
                ref={fila}
                role="tablist"
                className="-mx-4 flex gap-1.5 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:mx-0 sm:px-0 lg:flex-col lg:overflow-visible"
            >
                {secciones.map(({ id, etiqueta, Icono, peligro }, i) => {
                    const elegida = id === activa;
                    return (
                        <button
                            key={id}
                            ref={(el) => {
                                botones.current[i] = el;
                            }}
                            id={`pestana-${id}`}
                            type="button"
                            role="tab"
                            aria-selected={elegida}
                            aria-controls={`panel-${id}`}
                            tabIndex={elegida ? 0 : -1}
                            onClick={() => elegir(id)}
                            onKeyDown={(e) => alApretar(e, i)}
                            className={`flex shrink-0 items-center gap-2.5 whitespace-nowrap rounded-xl px-3.5 py-2.5 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                                elegida
                                    ? peligro
                                        ? 'bg-red-50 text-red-700'
                                        : 'bg-white text-marca-azul-profundo shadow-sm ring-1 ring-slate-200'
                                    : peligro
                                      ? 'text-red-600 hover:bg-red-50'
                                      : 'text-slate-600 hover:bg-white/70 hover:text-slate-900'
                            } ${peligro ? 'lg:mt-4' : ''}`}
                        >
                            <Icono className="h-4 w-4" aria-hidden="true" />
                            {etiqueta}
                        </button>
                    );
                })}
            </div>
        </nav>
    );
}

// Lee y escribe la sección elegida en el #hash de la URL.
function useSeccionActiva(ids: string[]) {
    const leerHash = () => {
        const hash =
            typeof window !== 'undefined'
                ? window.location.hash.replace('#', '')
                : '';
        return ids.includes(hash) ? hash : ids[0];
    };

    const [id, setId] = useState(leerHash);

    // Si cambian el hash a mano (o con atrás/adelante), seguirlo.
    useEffect(() => {
        const alCambiar = () => setId(leerHash());
        window.addEventListener('hashchange', alCambiar);
        return () => window.removeEventListener('hashchange', alCambiar);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ids.join()]);

    const elegir = (nuevo: string) => {
        setId(nuevo);
        // replaceState conservando el estado de Inertia, así no se rompe "atrás".
        window.history.replaceState(window.history.state, '', `#${nuevo}`);
    };

    // Al guardar, el backend redirige a /profile y la URL pierde el #hash:
    // lo volvemos a poner para que al recargar siga en la misma sección.
    // El setTimeout deja que Inertia termine de actualizar el historial antes.
    useEffect(
        () =>
            router.on('success', () => {
                setTimeout(() => {
                    const enPerfil =
                        window.location.pathname ===
                        new URL(route('profile.edit')).pathname;
                    if (enPerfil && window.location.hash !== `#${id}`) {
                        window.history.replaceState(
                            window.history.state,
                            '',
                            `#${id}`,
                        );
                    }
                });
            }),
        [id],
    );

    return { id, elegir };
}
