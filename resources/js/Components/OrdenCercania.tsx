import { PageProps } from '@/types';
import { irAlListado } from '@/utils/listado';
import { usePage } from '@inertiajs/react';
import { Clock, LoaderCircle, MapPin } from 'lucide-react';
import { useState } from 'react';

// "Ordenar por cercanía" (KAN-171). PublicacionController::index() ordena por
// distancia (Haversine) cuando recibe lat/lng en la URL.
// - Si el usuario tiene ubicación en el perfil, se usa esa sin preguntar.
// - Si no (todavía no hay selector: KAN-121), se le pide al navegador una vez.
//   Esa posición no se guarda en ningún lado.
// Las coordenadas viajan redondeadas a 2 decimales (~1 km): alcanza para ordenar
// y no deja la ubicación exacta en el historial ni en un link copiado.

const redondear = (valor: number) => Math.round(valor * 100) / 100;

export default function OrdenCercania({ activo }: { activo: boolean }) {
    const { auth } = usePage<PageProps>().props;
    const [buscando, setBuscando] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const tienePerfil = auth.user.lat != null && auth.user.lng != null;
    const hayNavegador =
        typeof navigator !== 'undefined' && 'geolocation' in navigator;

    // Sin ubicación posible no tiene sentido ofrecer la opción.
    if (!tienePerfil && !hayNavegador) return null;

    // irAlListado conserva los demás filtros (búsqueda, categoría) y vuelve a la página 1.
    const recargar = (ubicacion?: { lat: number; lng: number }) =>
        irAlListado(
            {
                lat: ubicacion ? redondear(ubicacion.lat) : null,
                lng: ubicacion ? redondear(ubicacion.lng) : null,
            },
            { onFinish: () => setBuscando(false) },
        );

    const ordenarPorCercania = () => {
        setError(null);
        setBuscando(true);

        if (tienePerfil) {
            recargar({
                lat: Number(auth.user.lat),
                lng: Number(auth.user.lng),
            });
            return;
        }

        navigator.geolocation.getCurrentPosition(
            ({ coords }) =>
                recargar({ lat: coords.latitude, lng: coords.longitude }),
            (fallo) => {
                setBuscando(false);
                setError(
                    fallo.code === fallo.PERMISSION_DENIED
                        ? 'Para ordenar por cercanía necesitamos tu ubicación. Podés habilitarla desde el candado de la barra de direcciones.'
                        : 'No pudimos obtener tu ubicación. Probá de nuevo en un rato.',
                );
            },
            // Una posición de hace 10 minutos sirve igual y evita esperar al GPS.
            { maximumAge: 10 * 60 * 1000, timeout: 10000 },
        );
    };

    const ordenarPorRecientes = () => {
        setError(null);
        recargar();
    };

    return (
        <div className="space-y-2">
            <div
                role="group"
                aria-label="Ordenar publicaciones"
                className="inline-flex rounded-full bg-white p-1 shadow-sm ring-1 ring-gray-200"
            >
                <Opcion
                    activa={!activo}
                    onClick={ordenarPorRecientes}
                    deshabilitada={buscando}
                >
                    <Clock className="h-4 w-4" aria-hidden="true" />
                    Más recientes
                </Opcion>
                <Opcion
                    activa={activo}
                    onClick={ordenarPorCercania}
                    deshabilitada={buscando}
                >
                    {buscando ? (
                        <LoaderCircle
                            className="h-4 w-4 animate-spin motion-reduce:animate-none"
                            aria-hidden="true"
                        />
                    ) : (
                        <MapPin className="h-4 w-4" aria-hidden="true" />
                    )}
                    {buscando ? 'Buscando…' : 'Más cercanas'}
                </Opcion>
            </div>

            <p aria-live="polite" className="text-sm text-red-600">
                {error}
            </p>
        </div>
    );
}

function Opcion({
    activa,
    onClick,
    deshabilitada,
    children,
}: {
    activa: boolean;
    onClick: () => void;
    deshabilitada: boolean;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            aria-pressed={activa}
            // Tocar la opción que ya está elegida no hace nada.
            onClick={activa ? undefined : onClick}
            disabled={deshabilitada}
            className={`inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:cursor-wait ${
                activa
                    ? 'bg-marca-azul text-white shadow-sm'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            }`}
        >
            {children}
        </button>
    );
}
