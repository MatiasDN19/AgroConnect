// Almacén chico de avisos flotantes (KAN-319), sin librerías.
// Vive fuera de React para que `avisar()` se pueda llamar desde cualquier lado
// (un onClick, un callback de Inertia, etc.). El contenedor lo lee con
// useSyncExternalStore.

export type TipoAviso = 'exito' | 'error' | 'info';

export interface Aviso {
    id: number;
    tipo: TipoAviso;
    texto: string;
    // Marcado para irse: el componente hace la animación de salida y después lo quita.
    saliendo: boolean;
}

// Si llega uno más, el más viejo se va solo.
const MAXIMO_VISIBLES = 3;

let avisos: Aviso[] = [];
let ultimoId = 0;
const oyentes = new Set<() => void>();

function emitir() {
    oyentes.forEach((oyente) => oyente());
}

export function avisar(tipo: TipoAviso, texto: string) {
    avisos = [...avisos, { id: ++ultimoId, tipo, texto, saliendo: false }];

    // Contamos solo los que no se están yendo.
    const visibles = avisos.filter((a) => !a.saliendo);
    if (visibles.length > MAXIMO_VISIBLES) {
        const sobrantes = new Set(
            visibles
                .slice(0, visibles.length - MAXIMO_VISIBLES)
                .map((a) => a.id),
        );
        avisos = avisos.map((a) =>
            sobrantes.has(a.id) ? { ...a, saliendo: true } : a,
        );
    }

    emitir();
}

// Pide que el aviso se vaya (con animación).
export function descartar(id: number) {
    avisos = avisos.map((a) => (a.id === id ? { ...a, saliendo: true } : a));
    emitir();
}

// Lo saca de la lista cuando ya terminó de irse.
export function quitar(id: number) {
    avisos = avisos.filter((a) => a.id !== id);
    emitir();
}

export function suscribir(oyente: () => void) {
    oyentes.add(oyente);
    return () => {
        oyentes.delete(oyente);
    };
}

export function obtenerAvisos() {
    return avisos;
}
