import { avisar } from '@/Components/Avisos/avisos';
import { router } from '@inertiajs/react';

// Lo común de las notificaciones (KAN-80, KAN-344): lo usan el panel de la
// campanita y la página /alertas.

export interface Alerta {
    id: number;
    tipo: string;
    mensaje: string;
    leida: boolean;
    created_at: string;
    // Publicación de la que habla la alerta (KAN-343). null en las alertas
    // viejas o si la publicación se borró.
    publicacion_id?: number | null;
}

interface Destino {
    url: string;
    // true si es el detalle de una publicación: puede que ya no se pueda ver
    // (bloqueada para terceros: 404), así que se confirma antes de ir.
    esPublicacion: boolean;
}

// A dónde lleva cada alerta al tocarla.
export function destinoAlerta(alerta: Alerta): Destino | null {
    const id = alerta.publicacion_id;

    switch (alerta.tipo) {
        // Las solicitudes van a su sección: ahí está el estado y las acciones.
        case 'nueva_solicitud_compra':
            return {
                url: route('solicitudes-compra.index'),
                esPublicacion: false,
            };
        case 'solicitud_compra_aceptada':
        case 'solicitud_compra_rechazada':
            return {
                url: route('solicitudes-compra.enviadas'),
                esPublicacion: false,
            };
        // Reseña: al detalle, bajando hasta las reseñas. Sin publicación
        // (alertas viejas), a "Mis publicaciones".
        case 'nueva_resena':
            return id
                ? {
                      url: `${route('publicaciones.show', id)}#resenas`,
                      esPublicacion: true,
                  }
                : { url: route('publicaciones.mias'), esPublicacion: false };
        // Denuncia (al que denunció y al dueño): al detalle de la publicación.
        case 'denuncia_resuelta':
            return id
                ? { url: route('publicaciones.show', id), esPublicacion: true }
                : null;
        default:
            return null;
    }
}

// Laravel deja el token CSRF en la cookie XSRF-TOKEN; fetch no lo manda solo.
function tokenXsrf(): string {
    const cookie = document.cookie
        .split('; ')
        .find((c) => c.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

// Pide solo el número del globito (y el flash fresco, ver Campanita).
export function actualizarContador() {
    router.reload({ only: ['alertas_no_leidas', 'flash'], async: true });
}

// Marca varias de una (KAN-323). El backend todavía no tiene un "marcar todas":
// se marcan de a una, en paralelo (suelen ser pocas). Cuando exista el endpoint,
// alcanza con cambiar esta función.
export async function marcarTodasLeidas(ids: number[]) {
    await Promise.all(ids.map((id) => marcarLeida(id)));
}

export async function marcarLeida(id: number) {
    try {
        await fetch(route('alertas.marcar-leida', id), {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': tokenXsrf(),
            },
        });
    } catch {
        // Si falla no es grave: queda sin leer y se reintenta otra vez.
    }
}

// Pedido liviano (HEAD, sin cuerpo) para saber si la publicación se puede ver.
// Si la red falla, probamos igual.
async function sePuedeVer(url: string): Promise<boolean> {
    try {
        const respuesta = await fetch(url, { method: 'HEAD' });
        return respuesta.ok;
    } catch {
        return true;
    }
}

// Marca la alerta como leída (si hacía falta) y lleva a su destino.
export async function abrirAlerta(
    alerta: Alerta,
    {
        alMarcarLeida,
        antesDeNavegar,
    }: { alMarcarLeida?: () => void; antesDeNavegar?: () => void } = {},
) {
    const destino = destinoAlerta(alerta);

    if (!alerta.leida) {
        // Se marca enseguida en pantalla y después se avisa al servidor.
        alMarcarLeida?.();
        await marcarLeida(alerta.id);
    }

    const puedeIr =
        destino && (!destino.esPublicacion || (await sePuedeVer(destino.url)));

    if (!destino || !puedeIr) {
        if (destino) {
            avisar('info', 'Esa publicación ya no está disponible.');
        }
        // Sin navegar, el globito no se entera solo de que bajó.
        if (!alerta.leida) actualizarContador();
        return;
    }

    // Si navegamos, la página nueva ya trae el número actualizado.
    antesDeNavegar?.();
    router.visit(destino.url);
}
