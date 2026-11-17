import { User } from '@/types';
import { Link } from '@inertiajs/react';
import { ArrowRight, Users } from 'lucide-react';
import { CSSProperties } from 'react';
import fotoAtardecer from '../../../images/landing/productor-atardecer.webp';
import { ctaPrincipal } from './cta';

// Posición en la entrada escalonada (cada uno arranca 90 ms después).
const orden = (n: number) => ({ '--orden': n }) as CSSProperties;

export default function Hero({
    usuario,
    canRegister,
    productores,
}: {
    usuario: User | null;
    canRegister: boolean;
    productores: number;
}) {
    const cta = ctaPrincipal(usuario, canRegister);

    return (
        <section className="px-4 pb-16 sm:px-6 lg:px-8">
            <div className="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-gradient-to-br from-marca-azul-claro via-marca-azul to-marca-azul-profundo px-6 py-12 text-white sm:px-10 lg:px-14 lg:py-16">
                {/* Círculos decorativos de fondo */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -bottom-32 left-1/3 h-80 w-80 rounded-full bg-white/5"
                />

                <div className="relative grid items-center gap-12 lg:grid-cols-[1.1fr,0.9fr]">
                    <div className="space-y-6">
                        <span
                            style={orden(0)}
                            className="revelar inline-block rounded-full bg-white/15 px-4 py-1.5 text-xs font-bold uppercase tracking-wider"
                        >
                            Consorcio Productivo Laguna Naineck
                        </span>

                        <h1
                            style={orden(1)}
                            className="revelar text-balance font-display text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl"
                        >
                            El campo se{' '}
                            <span className="relative inline-block font-acento font-normal text-marca-ambar">
                                conecta
                                <TrazoSubrayado />
                            </span>{' '}
                            directo con quien lo necesita
                        </h1>

                        <p
                            style={orden(2)}
                            className="revelar max-w-xl text-lg text-white/90"
                        >
                            Publicá, buscá y cerrá tratos con productores y
                            compradores de Laguna Naineck y alrededores. Sin
                            intermediarios, con precios claros y contacto
                            directo.
                        </p>

                        <div
                            style={orden(3)}
                            className="revelar flex flex-wrap gap-4 pt-2"
                        >
                            <Link
                                href={cta.href}
                                className="inline-flex items-center gap-2 rounded-xl bg-marca-ambar px-6 py-3.5 font-semibold text-amber-950 shadow-lg shadow-amber-900/20 transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-marca-azul"
                            >
                                {cta.texto}
                                <ArrowRight
                                    className="h-4 w-4"
                                    aria-hidden="true"
                                />
                            </Link>
                            <a
                                href="#como-funciona"
                                className="inline-flex items-center rounded-xl border border-white/60 px-6 py-3.5 font-semibold text-white transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            >
                                Cómo funciona
                            </a>
                        </div>
                    </div>

                    <div
                        style={orden(2)}
                        className="revelar relative mx-auto w-full max-w-md lg:max-w-none"
                    >
                        <img
                            src={fotoAtardecer}
                            alt="Productor trabajando la tierra al atardecer"
                            width={816}
                            height={1290}
                            className="aspect-[4/5] w-full rounded-3xl object-cover shadow-2xl ring-4 ring-white/20"
                        />

                        {/* Tarjeta flotante con un dato real de la base */}
                        {productores > 0 && (
                            <div className="flotar absolute -bottom-6 left-4 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 text-marca-tinta shadow-xl sm:-left-6">
                                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-marca-azul">
                                    <Users
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span className="leading-tight">
                                    <span className="block font-display text-xl font-extrabold">
                                        {productores.toLocaleString('es-AR')}
                                    </span>
                                    <span className="text-xs text-slate-500">
                                        {productores === 1
                                            ? 'productor en la plataforma'
                                            : 'productores en la plataforma'}
                                    </span>
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

// Trazo "a mano" debajo de la palabra destacada.
function TrazoSubrayado() {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 200 20"
            preserveAspectRatio="none"
            className="absolute -bottom-2 left-0 h-3 w-full text-marca-ambar"
        >
            <path
                className="trazo"
                d="M3 14 C 50 4, 110 4, 197 11"
                fill="none"
                stroke="currentColor"
                strokeWidth="4"
                strokeLinecap="round"
                pathLength={1}
            />
        </svg>
    );
}
