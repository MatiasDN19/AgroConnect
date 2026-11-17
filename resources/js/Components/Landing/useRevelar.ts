import { useEffect } from 'react';

// Hace aparecer los elementos con la clase .revelar cuando entran en pantalla,
// y los "resetea" cuando salen, así la animación se repite cada vez que volvés a ellos.
// - Aparece cuando al menos un 15% del elemento está a la vista.
// - Se resetea recién cuando salió COMPLETO de la pantalla, para que nunca
//   lo veas desaparecer mientras todavía lo estás mirando.
// - Sin JavaScript, o con "reducir movimiento" activado, no hace nada: todo se ve igual.
export default function useRevelar() {
    useEffect(() => {
        const reducir = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (reducir || !('IntersectionObserver' in window)) return;

        const elementos = document.querySelectorAll<HTMLElement>('.revelar');

        const observador = new IntersectionObserver(
            (entradas) => {
                entradas.forEach((entrada) => {
                    if (entrada.intersectionRatio >= 0.15) {
                        entrada.target.classList.add('revelado');
                    } else if (!entrada.isIntersecting) {
                        entrada.target.classList.remove('revelado');
                    }
                });
            },
            // Avisa al llegar al 0% (salió del todo) y al 15% (ya se ve).
            { threshold: [0, 0.15] },
        );

        elementos.forEach((elemento) => {
            elemento.classList.add('revelar-pendiente');
            observador.observe(elemento);
        });

        return () => observador.disconnect();
    }, []);
}
