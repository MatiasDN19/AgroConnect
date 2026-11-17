import { useEffect, useLayoutEffect, useRef, useState } from 'react';

const DURACION_MS = 1400;

// Curva de "frenado": arranca rápido y termina despacio.
const frenar = (t: number) => 1 - Math.pow(1 - t, 3);

// Número que sube de 0 al valor real cada vez que entra en pantalla.
// Cuando sale completo de la pantalla vuelve a 0, para contar de nuevo al volver.
// Con "reducir movimiento" muestra el valor directo.
export default function Contador({ valor }: { valor: number }) {
    const [mostrado, setMostrado] = useState(valor);
    const ref = useRef<HTMLSpanElement>(null);

    // Antes de pintar, si se puede animar, arrancamos en 0 (así no se ve un salto).
    useLayoutEffect(() => {
        const reducir = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (!reducir && 'IntersectionObserver' in window) {
            setMostrado(0);
        }
    }, []);

    useEffect(() => {
        const reducir = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        const elemento = ref.current;

        if (reducir || !elemento || !('IntersectionObserver' in window)) {
            setMostrado(valor);
            return;
        }

        let cuadro = 0;
        let contando = false;

        const contar = () => {
            contando = true;
            const inicio = performance.now();
            const animar = (ahora: number) => {
                const progreso = Math.min((ahora - inicio) / DURACION_MS, 1);
                setMostrado(Math.round(valor * frenar(progreso)));
                if (progreso < 1) {
                    cuadro = requestAnimationFrame(animar);
                }
            };
            cuadro = requestAnimationFrame(animar);
        };

        const observador = new IntersectionObserver(
            ([entrada]) => {
                if (entrada.intersectionRatio >= 0.5 && !contando) {
                    contar();
                } else if (!entrada.isIntersecting) {
                    // Salió completo: queda listo para contar de nuevo.
                    cancelAnimationFrame(cuadro);
                    contando = false;
                    setMostrado(0);
                }
            },
            { threshold: [0, 0.5] },
        );

        observador.observe(elemento);

        return () => {
            observador.disconnect();
            cancelAnimationFrame(cuadro);
        };
    }, [valor]);

    return (
        <span ref={ref} className="tabular-nums">
            {/* El lector de pantalla lee siempre el valor final, no los números intermedios. */}
            <span aria-hidden="true">{mostrado.toLocaleString('es-AR')}</span>
            <span className="sr-only">{valor.toLocaleString('es-AR')}</span>
        </span>
    );
}
