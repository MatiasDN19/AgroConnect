import ApplicationLogo from '@/Components/ApplicationLogo';
import { RedSocial } from '@/Components/Landing/Pie';
import { PageProps } from '@/types';
import { linkWhatsapp } from '@/utils/whatsapp';
import { Link, usePage } from '@inertiajs/react';
import { Mail, MapPin, MessageCircle } from 'lucide-react';

// Footer compacto de la app logueada (KAN-211). Más chico que el de la landing:
// marca, accesos y el contacto que carga el admin en Configuración (KAN-225).
// Solo se muestra lo que está cargado.
export default function PieApp() {
    const { contacto, auth } = usePage<PageProps>().props;
    const esProductor = auth.user.rol === 'productor';

    const whatsapp = contacto?.whatsapp
        ? linkWhatsapp(
              contacto.whatsapp,
              '¡Hola! Les escribo desde AgroConnect.',
          )
        : null;

    const claseLink =
        'text-white/70 transition-colors hover:text-white focus:outline-none focus-visible:text-marca-ambar';

    return (
        <footer className="mt-16 bg-marca-azul-profundo text-white">
            <div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.4fr,1fr,1.2fr] lg:px-8">
                <div className="space-y-3">
                    <Link
                        href={route('publicaciones.index')}
                        className="inline-flex items-center gap-2.5"
                    >
                        <ApplicationLogo className="h-11 w-11 rounded-full bg-white" />
                        <span className="font-display text-lg font-extrabold tracking-tight">
                            Agro
                            <span className="text-marca-ambar">Connect</span>
                        </span>
                    </Link>
                    <p className="max-w-xs text-sm text-white/70">
                        El marketplace del Consorcio Productivo de Laguna
                        Naineck.
                    </p>
                </div>

                <nav aria-label="Accesos" className="text-sm">
                    <ul className="grid grid-cols-2 gap-x-6 gap-y-2.5">
                        <li>
                            <Link
                                href={route('publicaciones.index')}
                                className={claseLink}
                            >
                                Marketplace
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={route('favoritos.index')}
                                className={claseLink}
                            >
                                Mis favoritos
                            </Link>
                        </li>
                        {esProductor && (
                            <li>
                                <Link
                                    href={route('publicaciones.create')}
                                    className={claseLink}
                                >
                                    Publicar
                                </Link>
                            </li>
                        )}
                        <li>
                            <Link
                                href={route('alertas.index')}
                                className={claseLink}
                            >
                                Notificaciones
                            </Link>
                        </li>
                        <li>
                            <Link
                                href={route('profile.edit')}
                                className={claseLink}
                            >
                                Mi perfil
                            </Link>
                        </li>
                        <li>
                            <Link href={route('landing')} className={claseLink}>
                                Sobre AgroConnect
                            </Link>
                        </li>
                    </ul>
                </nav>

                <address className="space-y-2.5 text-sm not-italic">
                    <p className="flex gap-2 text-white/70">
                        <MapPin
                            className="h-4 w-4 shrink-0 translate-y-0.5 text-marca-ambar"
                            aria-hidden="true"
                        />
                        {contacto?.direccion || 'Laguna Naineck, Formosa'}
                    </p>
                    {contacto?.email && (
                        <a
                            href={`mailto:${contacto.email}`}
                            className={`flex gap-2 ${claseLink}`}
                        >
                            <Mail
                                className="h-4 w-4 shrink-0 translate-y-0.5 text-marca-ambar"
                                aria-hidden="true"
                            />
                            <span className="break-all">{contacto.email}</span>
                        </a>
                    )}
                    {contacto?.whatsapp && (
                        <a
                            href={whatsapp ?? `tel:${contacto.whatsapp}`}
                            {...(whatsapp
                                ? {
                                      target: '_blank',
                                      rel: 'noopener noreferrer',
                                  }
                                : {})}
                            className={`flex gap-2 ${claseLink}`}
                        >
                            <MessageCircle
                                className="h-4 w-4 shrink-0 translate-y-0.5 text-marca-ambar"
                                aria-hidden="true"
                            />
                            {contacto.whatsapp}
                        </a>
                    )}
                    {(contacto?.facebook || contacto?.instagram) && (
                        <div className="flex gap-2 pt-1">
                            {contacto.facebook && (
                                <RedSocial
                                    href={contacto.facebook}
                                    nombre="Facebook"
                                >
                                    <path d="M24 12a12 12 0 1 0-13.9 11.9v-8.4h-3V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12Z" />
                                </RedSocial>
                            )}
                            {contacto.instagram && (
                                <RedSocial
                                    href={contacto.instagram}
                                    nombre="Instagram"
                                >
                                    <path d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 4.9a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8Zm0 8.1a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4Zm5.1-9.4a1.1 1.1 0 1 0 0 2.3 1.1 1.1 0 0 0 0-2.3Z" />
                                </RedSocial>
                            )}
                        </div>
                    )}
                </address>
            </div>

            <div className="border-t border-white/10">
                <p className="mx-auto max-w-7xl px-4 py-4 text-xs text-white/50 sm:px-6 lg:px-8">
                    © {new Date().getFullYear()} AgroConnect · Trabajo Final
                    Integrador, Programación (UTN)
                </p>
            </div>
        </footer>
    );
}
