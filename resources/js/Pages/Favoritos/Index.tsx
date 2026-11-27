import BotonFavorito from '@/Components/BotonFavorito';
import PublicacionCard, {
    PublicacionResumen,
} from '@/Components/PublicacionCard';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EstadoPublicacion, PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Heart } from 'lucide-react';

// "Mis favoritos" (KAN-188). Backend: FavoritoController::index (Iván).
// Usa la misma tarjeta del marketplace. Por ahora el backend no manda la foto
// de portada, las reseñas ni si el productor es socio: la tarjeta muestra el
// ícono de la categoría y no dice nada de reseñas hasta que llegue el dato.

interface Favorito {
    id: number;
    publicacion_id: number;
    publicacion: Omit<PublicacionResumen, 'usuario'> & {
        estado: EstadoPublicacion;
        usuario: { id: number; name: string; socio_validado?: boolean };
    };
}

// Las que no están activas se muestran igual, con una etiqueta.
const NO_DISPONIBLE: Partial<Record<EstadoPublicacion, string>> = {
    pausada: 'Pausada por el productor',
    vendida: 'Vendida',
    bloqueada: 'Ya no disponible',
};

export default function Index({
    favoritos,
}: PageProps<{ favoritos: Favorito[] }>) {
    return (
        <AuthenticatedLayout>
            <Head title="Mis favoritos" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <header className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h1 className="font-display text-3xl font-bold text-marca-tinta">
                        Mis favoritos
                    </h1>
                    {favoritos.length > 0 && (
                        <p className="text-sm text-slate-500">
                            {favoritos.length}{' '}
                            {favoritos.length === 1
                                ? 'publicación guardada'
                                : 'publicaciones guardadas'}
                        </p>
                    )}
                </header>

                {favoritos.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-200">
                        <span className="flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                            <Heart className="h-6 w-6" aria-hidden="true" />
                        </span>
                        <p className="font-medium text-slate-700">
                            Todavía no guardaste publicaciones.
                        </p>
                        <p className="max-w-sm text-sm text-slate-500">
                            Tocá "Guardar en favoritos" en una publicación para
                            tenerla a mano acá.
                        </p>
                        <Link
                            href={route('publicaciones.index')}
                            className="mt-1 text-sm font-medium text-marca-azul underline-offset-2 hover:underline"
                        >
                            Ir al marketplace →
                        </Link>
                    </div>
                ) : (
                    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {favoritos.map(({ id, publicacion }) => {
                            const etiqueta = NO_DISPONIBLE[publicacion.estado];

                            return (
                                <li key={id} className="relative">
                                    <div
                                        className={
                                            etiqueta
                                                ? 'opacity-60 grayscale-[40%]'
                                                : undefined
                                        }
                                    >
                                        <PublicacionCard
                                            publicacion={{
                                                ...publicacion,
                                                usuario: {
                                                    ...publicacion.usuario,
                                                    socio_validado:
                                                        publicacion.usuario
                                                            .socio_validado ??
                                                        false,
                                                },
                                            }}
                                        />
                                    </div>

                                    {etiqueta && (
                                        <span className="pointer-events-none absolute left-3 top-3 rounded-full bg-slate-900/80 px-3 py-1 text-xs font-semibold text-white">
                                            {etiqueta}
                                        </span>
                                    )}

                                    {/* Por encima del link de la tarjeta (z-20). */}
                                    <BotonFavorito
                                        publicacionId={publicacion.id}
                                        esFavorito
                                        variante="icono"
                                        className="absolute right-3 top-3 z-20"
                                    />
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
