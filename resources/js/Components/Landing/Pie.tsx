import { Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
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

                    {/* Ubicación */}
                    <div className="space-y-4">
                        <p className="font-display text-lg font-bold">
                            Dónde estamos
                        </p>
                        <p className="flex gap-2 text-white/70">
                            <MapPin
                                className="mt-0.5 h-5 w-5 shrink-0 text-marca-ambar"
                                aria-hidden="true"
                            />
                            <span>
                                Laguna Naineck, Formosa
                                <br />
                                Argentina
                            </span>
                        </p>
                    </div>
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
