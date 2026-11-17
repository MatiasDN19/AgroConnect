import { ArrowUp } from 'lucide-react';
import { useEffect, useState } from 'react';

// Botón flotante para volver al principio de la landing.
// Aparece recién después de bajar un poco, así no tapa el hero.
export default function BotonVolverArriba() {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const alScrollear = () => setVisible(window.scrollY > 600);

        alScrollear();
        window.addEventListener('scroll', alScrollear, { passive: true });

        return () => window.removeEventListener('scroll', alScrollear);
    }, []);

    const subir = () => {
        // Sin animación para quien pidió "reducir movimiento" en su sistema.
        const reducir = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        window.scrollTo({ top: 0, behavior: reducir ? 'auto' : 'smooth' });
    };

    return (
        <button
            type="button"
            onClick={subir}
            aria-label="Volver arriba"
            title="Volver arriba"
            tabIndex={visible ? 0 : -1}
            aria-hidden={!visible}
            className={`fixed bottom-6 right-6 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-marca-azul text-white shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:bg-marca-azul-profundo focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul focus-visible:ring-offset-2 motion-reduce:transition-none ${
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'pointer-events-none translate-y-4 opacity-0'
            }`}
        >
            <ArrowUp className="h-5 w-5" aria-hidden="true" />
        </button>
    );
}
