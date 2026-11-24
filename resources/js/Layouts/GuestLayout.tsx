import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, router, usePage } from '@inertiajs/react';
import { House, LogIn, LucideIcon, UserPlus } from 'lucide-react';
import { PropsWithChildren, useEffect, useRef, useState } from 'react';
import fotoIngresar from '../../images/auth/ingresar.webp';
import fotoRegistrarme from '../../images/auth/registrarme.webp';

// Marco de las pantallas de ingreso (KAN-231): barra con logo y pestañas, una
// tarjeta con foto y el panel del formulario.
//
// Login y Registro lo usan como layout PERSISTENTE (Login.layout = ...): Inertia no
// lo desmonta al pasar de /login a /register, y por eso se puede animar el cambio.
// La tarjeta es una "tira" vertical con las dos escenas apiladas (ingreso arriba,
// registro abajo): al ir al registro la tira sube y al volver al login baja.
// El formulario entra con el mismo movimiento. Sin animación si el usuario pidió
// "reducir movimiento" (ver app.css).

type Modo = 'ingreso' | 'registro';

// Texto de la escena de ingreso según la página (recuperar contraseña usa la misma foto).
const TEXTO_INGRESO: Record<string, { titulo: string; bajada: string }> = {
    'Auth/ForgotPassword': {
        titulo: 'Recuperá tu acceso',
        bajada: 'Te mandamos un enlace para que elijas una contraseña nueva.',
    },
    'Auth/ResetPassword': {
        titulo: 'Elegí una contraseña nueva',
        bajada: 'Y volvé a conectar con los productores de la zona.',
    },
    'Auth/ConfirmPassword': {
        titulo: 'Confirmá que sos vos',
        bajada: 'Es un paso de seguridad antes de seguir.',
    },
    'Auth/VerifyEmail': {
        titulo: 'Verificá tu correo',
        bajada: 'Así nos aseguramos de que la cuenta es tuya.',
    },
};

const TEXTO_INGRESO_DEFECTO = {
    titulo: 'Bienvenido de nuevo',
    bajada: 'Ingresá para comprar y vender con los productores de la zona.',
};

const TEXTO_REGISTRO = {
    titulo: 'Sumate a AgroConnect',
    bajada: 'Creá tu cuenta gratis y conectá con productores y compradores del Consorcio.',
};

export default function Guest({ children }: PropsWithChildren) {
    const { component } = usePage();
    const modo: Modo = component === 'Auth/Register' ? 'registro' : 'ingreso';

    // Dirección del formulario que entra: se calcula al cambiar de modo.
    // En la primera carga no hay animación (no venimos de ningún lado).
    const modoAnterior = useRef(modo);
    const direccion = useRef<'desde-abajo' | 'desde-arriba' | null>(null);
    if (modoAnterior.current !== modo) {
        direccion.current =
            modo === 'registro' ? 'desde-abajo' : 'desde-arriba';
        modoAnterior.current = modo;
    }

    // Animación optimista: apenas se hace clic en Ingresar/Registrarme, la tarjeta
    // empieza a deslizarse sin esperar la respuesta del servidor. Inertia avisa con
    // el evento "start"; con "finish" volvemos a seguir a la página real.
    // (Además la otra pestaña ya está precargada: ver PRECARGA más abajo.)
    const [modoDestino, setModoDestino] = useState<Modo | null>(null);
    useEffect(() => {
        const rutaRegistro = new URL(route('register')).pathname;
        const rutaLogin = new URL(route('login')).pathname;

        const quitarInicio = router.on('start', (evento) => {
            const ruta = evento.detail.visit.url.pathname;
            if (ruta === rutaRegistro) setModoDestino('registro');
            else if (ruta === rutaLogin) setModoDestino('ingreso');
        });
        const quitarFin = router.on('finish', () => setModoDestino(null));

        return () => {
            quitarInicio();
            quitarFin();
        };
    }, []);
    const modoVisual = modoDestino ?? modo;

    const textoIngreso = TEXTO_INGRESO[component] ?? TEXTO_INGRESO_DEFECTO;

    // Confirmar contraseña y verificar email los ve alguien que YA inició sesión:
    // ahí no tienen sentido las pestañas Ingresar / Registrarme.
    const conPestanas = !['Auth/ConfirmPassword', 'Auth/VerifyEmail'].includes(
        component,
    );

    // Fondo con el mismo degradé azul del hero de la landing: menos blanco y más marca.
    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-marca-azul-claro via-marca-azul to-marca-azul-profundo px-4 py-8 text-marca-tinta sm:py-14">
            <div className="w-full max-w-5xl">
                {/* Altura FIJA en escritorio (alcanza para el registro, que es el más largo):
                    así la tarjeta de la foto mide siempre lo mismo y el deslizamiento no cambia de tamaño. */}
                <div className="rounded-3xl bg-white shadow-2xl shadow-marca-tinta/30 lg:grid lg:h-[43rem] lg:grid-cols-[7.5rem_minmax(0,24rem)_minmax(0,1fr)]">
                    {/* Barra lateral (escritorio) */}
                    <nav
                        aria-label="Ingreso a AgroConnect"
                        className="hidden flex-col items-center justify-between py-10 lg:flex"
                    >
                        <Link
                            href={route('landing')}
                            aria-label="Ir a la página de inicio de AgroConnect"
                            className="rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                        >
                            <ApplicationLogo className="h-20 w-20" />
                        </Link>

                        <div
                            className={`flex flex-col gap-12 ${conPestanas ? '' : 'invisible'}`}
                        >
                            <PestanaLateral
                                href={route('login')}
                                activa={modoVisual === 'ingreso'}
                                Icono={LogIn}
                                texto="Ingresar"
                            />
                            <PestanaLateral
                                href={route('register')}
                                activa={modoVisual === 'registro'}
                                Icono={UserPlus}
                                texto="Registrarme"
                            />
                        </div>

                        <Link
                            href={route('landing')}
                            className="flex flex-col items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-slate-400 transition hover:text-marca-azul focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                        >
                            <House className="h-5 w-5" aria-hidden="true" />
                            Inicio
                        </Link>
                    </nav>

                    {/* Tarjeta con la foto: banner arriba en celular, columna alta en escritorio */}
                    <div
                        aria-hidden="true"
                        className="relative m-3 h-44 overflow-hidden rounded-2xl shadow-lg sm:h-56 lg:m-0 lg:-my-10 lg:h-auto lg:rounded-[1.75rem] lg:shadow-2xl lg:shadow-marca-tinta/25"
                    >
                        <div
                            className="absolute inset-x-0 top-0 h-[200%] transition-transform duration-700 ease-[cubic-bezier(0.7,0,0.2,1)] motion-reduce:transition-none"
                            style={{
                                transform:
                                    modoVisual === 'registro'
                                        ? 'translateY(-50%)'
                                        : 'translateY(0)',
                            }}
                        >
                            <Escena
                                foto={fotoIngresar}
                                titulo={textoIngreso.titulo}
                                bajada={textoIngreso.bajada}
                            />
                            <Escena
                                foto={fotoRegistrarme}
                                titulo={TEXTO_REGISTRO.titulo}
                                bajada={TEXTO_REGISTRO.bajada}
                            />
                        </div>
                    </div>

                    {/* Panel del formulario */}
                    <main className="px-5 pb-8 pt-4 sm:px-10 lg:flex lg:flex-col lg:justify-center lg:overflow-y-auto lg:py-8 xl:px-14">
                        {/* En celular no hay barra lateral: el "volver" va arriba, chiquito */}
                        <Link
                            href={route('landing')}
                            className="mb-4 inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-marca-azul lg:hidden"
                        >
                            <House className="h-4 w-4" aria-hidden="true" />
                            Volver al inicio
                        </Link>

                        {/* Pestañas en celular (en escritorio están en la barra lateral) */}
                        <nav
                            aria-label="Ingreso a AgroConnect"
                            className={`mb-6 grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 lg:hidden ${conPestanas ? 'grid' : 'hidden'}`}
                        >
                            <PestanaCelular
                                href={route('login')}
                                activa={modoVisual === 'ingreso'}
                                texto="Ingresar"
                            />
                            <PestanaCelular
                                href={route('register')}
                                activa={modoVisual === 'registro'}
                                texto="Registrarme"
                            />
                        </nav>

                        <div
                            key={component}
                            className={
                                direccion.current
                                    ? `auth-entrar-${direccion.current}`
                                    : ''
                            }
                        >
                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}

// Precarga de la OTRA pantalla de ingreso (fix de KAN-231).
// - "mount": se pide apenas se abre la página, así en celular (donde no hay "pasar el
//   mouse") y con clics rápidos ya está lista y no aparece la barra de carga.
// - "hover": si venció, se vuelve a pedir al pasar el mouse.
// - cacheFor: por defecto la precarga dura 30 s; estas páginas no tienen datos que
//   cambien, así que dura 10 minutos (alguien puede tardar en completar el formulario).
export const PRECARGA: {
    prefetch: ('mount' | 'hover' | 'click')[];
    cacheFor: string;
} = {
    prefetch: ['mount', 'hover'],
    cacheFor: '10m',
};

// La pestaña activa es la página actual: no hace falta precargarla.
function precargaPestana(activa: boolean) {
    return activa ? {} : PRECARGA;
}

// Una escena de la tira: foto, degradé para que se lea el texto, título y bajada.
function Escena({
    foto,
    titulo,
    bajada,
}: {
    foto: string;
    titulo: string;
    bajada: string;
}) {
    return (
        <div className="relative h-1/2">
            <img
                src={foto}
                alt=""
                className="absolute inset-0 h-full w-full object-cover"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-marca-tinta/90 via-marca-tinta/35 to-transparent" />
            <div className="absolute inset-x-0 bottom-0 space-y-1 p-5 text-center text-white sm:p-8 lg:pb-14">
                <p className="font-display text-xl font-extrabold tracking-tight sm:text-2xl lg:text-3xl">
                    {titulo}
                </p>
                <p className="mx-auto max-w-xs text-sm text-white/85">
                    {bajada}
                </p>
                {/* Todavía no hay página de términos: queda visible como "próximamente". */}
                <p className="hidden pt-3 text-xs text-white/60 lg:block">
                    Términos y condiciones · Próximamente
                </p>
            </div>
        </div>
    );
}

function PestanaLateral({
    href,
    activa,
    Icono,
    texto,
}: {
    href: string;
    activa: boolean;
    Icono: LucideIcon;
    texto: string;
}) {
    return (
        <Link
            href={href}
            {...precargaPestana(activa)}
            aria-current={activa ? 'page' : undefined}
            className={`relative flex flex-col items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                activa
                    ? 'text-marca-azul'
                    : 'text-slate-400 hover:text-slate-600'
            }`}
        >
            {/* Barrita de la pestaña activa, pegada al borde de la tarjeta */}
            <span
                aria-hidden="true"
                className={`absolute -left-6 top-1/2 h-10 w-1 -translate-y-1/2 rounded-r-full bg-marca-azul transition-opacity duration-300 ${
                    activa ? 'opacity-100' : 'opacity-0'
                }`}
            />
            <Icono className="h-6 w-6" aria-hidden="true" />
            {texto}
        </Link>
    );
}

function PestanaCelular({
    href,
    activa,
    texto,
}: {
    href: string;
    activa: boolean;
    texto: string;
}) {
    return (
        <Link
            href={href}
            {...precargaPestana(activa)}
            aria-current={activa ? 'page' : undefined}
            className={`rounded-lg py-2 text-center text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul ${
                activa
                    ? 'bg-white text-marca-tinta shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'
            }`}
        >
            {texto}
        </Link>
    );
}
