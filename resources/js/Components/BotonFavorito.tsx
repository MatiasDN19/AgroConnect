import { router } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { useEffect, useState } from 'react';

// Guardar o sacar una publicación de favoritos (KAN-188). Backend: KAN-247 y
// FavoritoController (POST/DELETE /publicaciones/{id}/favorito, vuelven con un
// aviso). Cambia al toque y, si el pedido falla, vuelve a como estaba.
//   - "completo": botón con texto, para el detalle.
//   - "icono": solo el corazón, para ponerlo encima de una tarjeta.

export default function BotonFavorito({
    publicacionId,
    esFavorito,
    variante = 'completo',
    className = '',
}: {
    publicacionId: number;
    esFavorito: boolean;
    variante?: 'completo' | 'icono';
    className?: string;
}) {
    const [activo, setActivo] = useState(esFavorito);
    const [guardando, setGuardando] = useState(false);

    // Si la página trae otro valor (por ejemplo, después de recargar), seguirlo.
    useEffect(() => setActivo(esFavorito), [esFavorito]);

    const cambiar = () => {
        const nuevo = !activo;
        setActivo(nuevo);
        const opciones = {
            preserveScroll: true,
            onError: () => setActivo(!nuevo),
            onFinish: () => setGuardando(false),
        };
        setGuardando(true);
        if (nuevo) {
            router.post(route('favoritos.store', publicacionId), {}, opciones);
        } else {
            router.delete(route('favoritos.destroy', publicacionId), opciones);
        }
    };

    const etiqueta = activo ? 'Sacar de favoritos' : 'Guardar en favoritos';

    if (variante === 'icono') {
        return (
            <button
                type="button"
                onClick={cambiar}
                disabled={guardando}
                aria-pressed={activo}
                aria-label={etiqueta}
                title={etiqueta}
                className={`flex h-10 w-10 items-center justify-center rounded-full bg-white/95 shadow-md ring-1 ring-slate-200 backdrop-blur transition-transform duration-150 ease-out hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul active:scale-95 disabled:cursor-wait ${className}`}
            >
                <Corazon activo={activo} />
            </button>
        );
    }

    return (
        <button
            type="button"
            onClick={cambiar}
            disabled={guardando}
            aria-pressed={activo}
            className={`inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium ring-1 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul active:scale-[0.97] disabled:cursor-wait ${
                activo
                    ? 'bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100'
                    : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'
            } ${className}`}
        >
            <Corazon activo={activo} />
            {activo ? 'En tus favoritos' : 'Guardar en favoritos'}
        </button>
    );
}

// El corazón se rellena al guardar, con un "pop" corto (sin animación si se
// pidió reducir movimiento).
function Corazon({ activo }: { activo: boolean }) {
    return (
        <Heart
            key={String(activo)}
            aria-hidden="true"
            className={`h-4 w-4 transition-colors duration-150 ${
                activo
                    ? 'globito-pop fill-rose-500 text-rose-500'
                    : 'text-slate-500'
            }`}
        />
    );
}
