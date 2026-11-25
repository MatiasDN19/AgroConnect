import { PageProps } from '@/types';
import { linkWhatsapp } from '@/utils/whatsapp';
import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin, MessageCircle } from 'lucide-react';
import logoConsorcio from '../../../images/logos/consorcio-laguna-naineck.webp';
import logoPlanNutrir from '../../../images/logos/plan-nutrir.webp';
import { LogoConNombre, SECCIONES } from './Navegacion';

const EQUIPO = ['Matías Nuñez', 'Nadia Medina', 'Iván Olmedo'];

const claseLink =
    'text-white/70 transition hover:text-marca-ambar focus:outline-none focus-visible:text-marca-ambar';

export default function Pie() {
    return (
        <footer className="relative overflow-hidden bg-marca-azul-profundo px-4 pb-8 pt-16 text-white sm:px-6 lg:px-8">
            {/* Palabra gigante de fondo, solo decorativa */}
            <p
                aria-hidden="true"
                className="pointer-events-none absolute -bottom-[0.18em] left-1/2 -translate-x-1/2 select-none whitespace-nowrap font-display text-[19vw] font-extrabold leading-none tracking-tighter text-white/[0.06]"
            >
                AgroConnect
            </p>

            <div className="relative mx-auto max-w-7xl">
                <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.6fr,1fr,1fr,1.2fr]">
                    {/* Marca */}
                    <div className="space-y-5 sm:col-span-2 lg:col-span-1">
                        <Link href="/" aria-label="AgroConnect, inicio">
                            <LogoConNombre claro />
                        </Link>
                        <p className="max-w-sm text-white/70">
                            El marketplace del Consorcio Productivo de Laguna
                            Naineck. Productores y compradores de la región,
                            conectados de forma directa.
                        </p>

                        <div className="space-y-2">
                            <p className="text-xs font-bold uppercase tracking-[0.18em] text-marca-ambar">
                                Con el apoyo de
                            </p>
                            {/* Placa blanca: los logos institucionales son a color */}
                            <div className="inline-flex items-center gap-5 rounded-2xl bg-white px-5 py-3">
                                <img
                                    src={logoConsorcio}
                                    alt="Consorcio Productivo de Laguna Naineck"
                                    className="h-16 w-auto"
                                    loading="lazy"
                                />
                                <img
                                    src={logoPlanNutrir}
                                    alt="Plan Nutrir, plan provincial alimentario"
                                    className="h-11 w-auto"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Navegación por la landing */}
                    <nav
                        aria-label="Secciones de la página"
                        className="space-y-4"
                    >
                        <p className="font-display text-lg font-bold">
                            Navegación
                        </p>
                        <ul className="space-y-2.5">
                            {SECCIONES.map((seccion) => (
                                <li key={seccion.id}>
                                    <a
                                        href={`#${seccion.id}`}
                                        className={claseLink}
                                    >
                                        {seccion.texto}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    {/* Accesos a la plataforma */}
                    <nav aria-label="Plataforma" className="space-y-4">
                        <p className="font-display text-lg font-bold">
                            Plataforma
                        </p>
                        <ul className="space-y-2.5">
                            <li>
                                <Link
                                    href={route('login')}
                                    className={claseLink}
                                >
                                    Ingresar
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={route('register')}
                                    className={claseLink}
                                >
                                    Crear cuenta
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={route('publicaciones.index')}
                                    className={claseLink}
                                >
                                    Marketplace
                                </Link>
                            </li>
                        </ul>
                    </nav>

                    <ColumnaContacto />
                </div>

                <div className="mt-14 flex flex-col gap-3 border-t border-white/15 pt-6 text-sm text-white/60 md:flex-row md:items-center md:justify-between">
                    <p>
                        © {new Date().getFullYear()} AgroConnect · Trabajo Final
                        Integrador, carrera de Programación (UTN)
                    </p>
                    <p>
                        Desarrollado por{' '}
                        <span className="text-white/85">
                            {EQUIPO.join(' · ')}
                        </span>
                    </p>
                </div>
            </div>
        </footer>
    );
}

// Contacto (KAN-225): lo que carga el admin en Configuración. Solo se muestra lo que
// está cargado; la ubicación siempre aparece (si no hay dirección, la del Consorcio).
function ColumnaContacto() {
    const { contacto } = usePage<PageProps>().props;

    const whatsapp = contacto?.whatsapp
        ? linkWhatsapp(
              contacto.whatsapp,
              '¡Hola! Les escribo desde la página de AgroConnect.',
          )
        : null;

    const claseLink =
        'flex gap-2 text-white/70 transition hover:text-white focus:outline-none focus-visible:underline';

    return (
        <address className="space-y-4 not-italic">
            <p className="font-display text-lg font-bold">Contacto</p>

            <p className="flex gap-2 text-white/70">
                <MapPin
                    className="mt-0.5 h-5 w-5 shrink-0 text-marca-ambar"
                    aria-hidden="true"
                />
                <span>
                    {contacto?.direccion || (
                        <>
                            Laguna Naineck, Formosa
                            <br />
                            Argentina
                        </>
                    )}
                </span>
            </p>

            {contacto?.email && (
                <a href={`mailto:${contacto.email}`} className={claseLink}>
                    <Mail
                        className="mt-0.5 h-5 w-5 shrink-0 text-marca-ambar"
                        aria-hidden="true"
                    />
                    <span className="break-all">{contacto.email}</span>
                </a>
            )}

            {contacto?.whatsapp && (
                <a
                    href={whatsapp ?? `tel:${contacto.whatsapp}`}
                    {...(whatsapp
                        ? { target: '_blank', rel: 'noopener noreferrer' }
                        : {})}
                    className={claseLink}
                >
                    <MessageCircle
                        className="mt-0.5 h-5 w-5 shrink-0 text-marca-ambar"
                        aria-hidden="true"
                    />
                    <span>
                        {contacto.whatsapp}
                        {whatsapp && (
                            <span className="sr-only">
                                {' '}
                                (WhatsApp, se abre en una pestaña nueva)
                            </span>
                        )}
                    </span>
                </a>
            )}

            {(contacto?.facebook || contacto?.instagram) && (
                <div className="flex gap-3 pt-1">
                    {contacto.facebook && (
                        <RedSocial href={contacto.facebook} nombre="Facebook">
                            <path d="M24 12a12 12 0 1 0-13.9 11.9v-8.4h-3V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12Z" />
                        </RedSocial>
                    )}
                    {contacto.instagram && (
                        <RedSocial href={contacto.instagram} nombre="Instagram">
                            <path d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 4.9a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8Zm0 8.1a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4Zm5.1-9.4a1.1 1.1 0 1 0 0 2.3 1.1 1.1 0 0 0 0-2.3Z" />
                        </RedSocial>
                    )}
                </div>
            )}
        </address>
    );
}

export function RedSocial({
    href,
    nombre,
    children,
}: {
    href: string;
    nombre: string;
    children: React.ReactNode;
}) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={`${nombre} de AgroConnect (se abre en una pestaña nueva)`}
            className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white/80 transition hover:bg-white/20 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-ambar"
        >
            <svg
                viewBox="0 0 24 24"
                className="h-5 w-5"
                fill="currentColor"
                aria-hidden="true"
            >
                {children}
            </svg>
        </a>
    );
}
