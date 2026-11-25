import { router } from '@inertiajs/react';

// Cambia los filtros del listado de publicaciones (KAN-211) sin perder los demás:
// toma lo que ya está en la URL (q, categoria_id, lat/lng...), aplica los cambios
// (null o '' borra el parámetro) y vuelve a la página 1, porque con otro filtro u
// orden la página en la que estabas deja de tener sentido.
export function irAlListado(
    cambios: Record<string, string | number | null | undefined>,
    opciones: { onFinish?: () => void } = {},
) {
    const parametros: Record<string, string | number> = Object.fromEntries(
        new URLSearchParams(window.location.search),
    );
    delete parametros.page;

    for (const [clave, valor] of Object.entries(cambios)) {
        if (valor === null || valor === undefined || valor === '') {
            delete parametros[clave];
        } else {
            parametros[clave] = valor;
        }
    }

    router.get(route('publicaciones.index'), parametros, {
        preserveState: true,
        preserveScroll: true,
        onFinish: opciones.onFinish,
    });
}
