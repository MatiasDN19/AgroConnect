import { User } from '@/types';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { ctaPrincipal } from './cta';

export default function CtaFinal({
    usuario,
    canRegister,
}: {
    usuario: User | null;
    canRegister: boolean;
}) {
    const cta = ctaPrincipal(usuario, canRegister);

    return (
        <section className="px-4 pb-20 sm:px-6 lg:px-8">
            <div className="revelar relative mx-auto flex max-w-7xl flex-col items-start gap-6 overflow-hidden rounded-[2rem] bg-marca-azul-profundo px-6 py-12 text-white sm:px-12 lg:flex-row lg:items-center lg:justify-between">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10"
                />

                <div className="relative max-w-2xl space-y-3">
                    <h2 className="text-balance font-display text-3xl font-extrabold leading-tight sm:text-4xl">
                        Sumate al marketplace del Consorcio
                    </h2>
                    <p className="text-white/80">
                        Si sos productor, comprador o las dos cosas, AgroConnect
                        es el punto de encuentro de Laguna Naineck y la región.
                    </p>
                </div>

                <Link
                    href={cta.href}
                    className="relative inline-flex shrink-0 items-center gap-2 rounded-xl bg-marca-ambar px-6 py-3.5 font-semibold text-amber-950 shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-marca-azul-profundo"
                >
                    {cta.texto}
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                </Link>
            </div>
        </section>
    );
}
