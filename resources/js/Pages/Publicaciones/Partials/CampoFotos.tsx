import { PublicacionFoto } from '@/types';
import { comprimirImagen } from '@/utils/imagenes';
import {
    ArrowLeft,
    ArrowRight,
    ImagePlus,
    Star,
    Trash2,
    Undo2,
} from 'lucide-react';
import { DragEvent, useEffect, useMemo, useRef, useState } from 'react';

// Campo de fotos del formulario de publicación (KAN-205).
// - Hasta `maximo` fotos en total (las que ya tiene + las nuevas) y de hasta
//   `tamanoMaxKb` cada una: son los límites que configura el admin (KAN-225) y que
//   el backend usa para validar, así que nunca quedan desfasados.
// - Las fotos se achican en el navegador antes de subirlas (utils/imagenes.ts).
// - La primera es la portada. El backend agrega las nuevas DESPUÉS de las existentes
//   y no permite reordenar las ya guardadas: por eso solo se ordenan las nuevas, y una
//   nueva puede ser portada únicamente si no queda ninguna de las que ya estaban.
const FORMATOS = 'image/jpeg,image/png,image/webp';

// 2048 -> "2 MB", 1536 -> "1,5 MB", 512 -> "512 KB" (igual que los mensajes del backend).
const textoTamano = (kb: number) =>
    kb < 1024
        ? `${kb} KB`
        : `${String(Math.round((kb / 1024) * 10) / 10).replace('.', ',')} MB`;

export default function CampoFotos({
    existentes = [],
    eliminar,
    onEliminarChange,
    nuevas,
    onNuevasChange,
    errores,
    maximo,
    tamanoMaxKb,
}: {
    existentes?: PublicacionFoto[];
    eliminar: number[];
    onEliminarChange: (ids: number[]) => void;
    nuevas: File[];
    onNuevasChange: (archivos: File[]) => void;
    errores: string[];
    maximo: number;
    tamanoMaxKb: number;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [procesando, setProcesando] = useState(false);
    const [aviso, setAviso] = useState<string | null>(null);
    const [arrastrando, setArrastrando] = useState(false);

    const quedan = existentes.filter((f) => !eliminar.includes(f.id));
    const total = quedan.length + nuevas.length;
    const lugares = maximo - total;

    // Vistas previas de las nuevas. Se liberan (revokeObjectURL) cuando cambia la
    // lista o se desmonta el campo, para no dejar memoria tomada.
    const previas = useMemo(
        () => nuevas.map((archivo) => URL.createObjectURL(archivo)),
        [nuevas],
    );
    useEffect(
        () => () => previas.forEach((url) => URL.revokeObjectURL(url)),
        [previas],
    );

    const agregar = async (lista: FileList | File[]) => {
        const archivos = Array.from(lista).filter((a) =>
            a.type.startsWith('image/'),
        );
        if (archivos.length === 0) return;

        setAviso(
            archivos.length > lugares
                ? `Se agregaron ${Math.max(lugares, 0)} de ${archivos.length}: el máximo es ${maximo} fotos por publicación.`
                : null,
        );

        const aSubir = archivos.slice(0, Math.max(lugares, 0));
        if (aSubir.length === 0) return;

        setProcesando(true);
        const comprimidas = await Promise.all(aSubir.map(comprimirImagen));
        setProcesando(false);

        // Si alguna sigue pesando más que el límite después de achicarla (pasa con
        // límites muy chicos o formatos que el navegador no puede recomprimir), no se
        // agrega y se avisa ahora, en vez de esperar el error del servidor.
        const entran = comprimidas.filter((a) => a.size <= tamanoMaxKb * 1024);
        const noEntran = comprimidas.length - entran.length;
        if (noEntran > 0) {
            setAviso(
                `${noEntran === 1 ? 'Una foto pesa' : `${noEntran} fotos pesan`} más de ${textoTamano(tamanoMaxKb)} y no se agregó. Probá con otra o recortala.`,
            );
        }

        onNuevasChange([...nuevas, ...entran]);
    };

    const mover = (desde: number, hasta: number) => {
        const lista = [...nuevas];
        const [archivo] = lista.splice(desde, 1);
        lista.splice(hasta, 0, archivo);
        onNuevasChange(lista);
    };

    const quitarNueva = (indice: number) =>
        onNuevasChange(nuevas.filter((_, i) => i !== indice));

    const alternarEliminar = (id: number) =>
        onEliminarChange(
            eliminar.includes(id)
                ? eliminar.filter((e) => e !== id)
                : [...eliminar, id],
        );

    const soltar = (e: DragEvent) => {
        e.preventDefault();
        setArrastrando(false);
        if (!procesando) agregar(e.dataTransfer.files);
    };

    // La portada es la primera existente que queda; si no hay, la primera nueva.
    const portadaEsNueva = quedan.length === 0;

    return (
        <div>
            <div className="flex items-baseline justify-between gap-4">
                <span
                    id="fotos-etiqueta"
                    className="block text-sm font-medium text-gray-700"
                >
                    Fotos (opcional)
                </span>
                <span className="text-xs text-gray-500">
                    {total} de {maximo}
                </span>
            </div>

            {(existentes.length > 0 || nuevas.length > 0) && (
                <ul
                    aria-labelledby="fotos-etiqueta"
                    className="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-4"
                >
                    {existentes.map((foto) => {
                        const seQuita = eliminar.includes(foto.id);
                        const esPortada = !seQuita && quedan[0]?.id === foto.id;

                        return (
                            <li
                                key={`existente-${foto.id}`}
                                className="relative aspect-square overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200"
                            >
                                <img
                                    src={foto.url}
                                    alt=""
                                    className={`h-full w-full object-cover transition ${seQuita ? 'opacity-30 grayscale' : ''}`}
                                />
                                {esPortada && <EtiquetaPortada />}
                                {seQuita && (
                                    <span className="absolute inset-x-0 top-1/2 -translate-y-1/2 text-center text-xs font-semibold text-gray-700">
                                        Se va a quitar
                                    </span>
                                )}
                                <div className="absolute bottom-1 right-1 flex gap-1">
                                    <BotonMiniatura
                                        etiqueta={
                                            seQuita
                                                ? 'Deshacer: no quitar esta foto'
                                                : 'Quitar esta foto'
                                        }
                                        onClick={() =>
                                            alternarEliminar(foto.id)
                                        }
                                    >
                                        {seQuita ? (
                                            <Undo2 className="h-3.5 w-3.5" />
                                        ) : (
                                            <Trash2 className="h-3.5 w-3.5" />
                                        )}
                                    </BotonMiniatura>
                                </div>
                            </li>
                        );
                    })}

                    {nuevas.map((archivo, i) => {
                        const esPortada = portadaEsNueva && i === 0;

                        return (
                            <li
                                key={previas[i]}
                                className="relative aspect-square overflow-hidden rounded-lg bg-gray-100 ring-2 ring-indigo-200"
                            >
                                <img
                                    src={previas[i]}
                                    alt={`Foto nueva ${i + 1}: ${archivo.name}`}
                                    className="h-full w-full object-cover"
                                />
                                {esPortada ? (
                                    <EtiquetaPortada />
                                ) : (
                                    <span className="absolute left-1 top-1 rounded bg-indigo-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                        Nueva
                                    </span>
                                )}
                                <div className="absolute inset-x-1 bottom-1 flex justify-between gap-1">
                                    <div className="flex gap-1">
                                        {i > 0 && (
                                            <BotonMiniatura
                                                etiqueta="Mover a la izquierda"
                                                onClick={() => mover(i, i - 1)}
                                            >
                                                <ArrowLeft className="h-3.5 w-3.5" />
                                            </BotonMiniatura>
                                        )}
                                        {i < nuevas.length - 1 && (
                                            <BotonMiniatura
                                                etiqueta="Mover a la derecha"
                                                onClick={() => mover(i, i + 1)}
                                            >
                                                <ArrowRight className="h-3.5 w-3.5" />
                                            </BotonMiniatura>
                                        )}
                                    </div>
                                    <div className="flex gap-1">
                                        {portadaEsNueva && i > 0 && (
                                            <BotonMiniatura
                                                etiqueta="Usar como portada"
                                                onClick={() => mover(i, 0)}
                                            >
                                                <Star className="h-3.5 w-3.5" />
                                            </BotonMiniatura>
                                        )}
                                        <BotonMiniatura
                                            etiqueta="Quitar esta foto"
                                            onClick={() => quitarNueva(i)}
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </BotonMiniatura>
                                    </div>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            {lugares > 0 && (
                <div
                    onDragOver={(e) => {
                        e.preventDefault();
                        setArrastrando(true);
                    }}
                    onDragLeave={() => setArrastrando(false)}
                    onDrop={soltar}
                    className={`mt-3 flex flex-col items-center gap-2 rounded-lg border-2 border-dashed px-4 py-6 text-center transition ${
                        arrastrando
                            ? 'border-indigo-400 bg-indigo-50'
                            : 'border-gray-300'
                    }`}
                >
                    <ImagePlus
                        className="h-8 w-8 text-gray-400"
                        aria-hidden="true"
                    />
                    <button
                        type="button"
                        onClick={() => input.current?.click()}
                        disabled={procesando}
                        aria-describedby="fotos-ayuda"
                        className="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-indigo-700 ring-1 ring-indigo-200 transition hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:opacity-60"
                    >
                        {procesando ? 'Preparando fotos…' : 'Elegir fotos'}
                    </button>
                    <p id="fotos-ayuda" className="text-xs text-gray-500">
                        o arrastralas acá. JPG, PNG o WebP · hasta{' '}
                        {lugares === maximo
                            ? `${maximo} ${maximo === 1 ? 'foto' : 'fotos'}`
                            : `${lugares} más`}
                        . Las achicamos automáticamente para que suban rápido.
                    </p>
                    <input
                        ref={input}
                        type="file"
                        multiple
                        accept={FORMATOS}
                        className="sr-only"
                        tabIndex={-1}
                        aria-hidden="true"
                        onChange={(e) => {
                            if (e.target.files) agregar(e.target.files);
                            // Permite volver a elegir el mismo archivo después de quitarlo.
                            e.target.value = '';
                        }}
                    />
                </div>
            )}

            {existentes.length > 0 && nuevas.length > 0 && !portadaEsNueva && (
                <p className="mt-2 text-xs text-gray-500">
                    Las fotos nuevas se agregan después de las que ya tenía la
                    publicación. Para usar una nueva como portada, quitá las
                    anteriores.
                </p>
            )}

            {aviso && (
                <p role="status" className="mt-2 text-sm text-amber-700">
                    {aviso}
                </p>
            )}

            {errores.map((error) => (
                <p key={error} className="mt-2 text-sm text-red-600">
                    {error}
                </p>
            ))}
        </div>
    );
}

function EtiquetaPortada() {
    return (
        <span className="absolute left-1 top-1 inline-flex items-center gap-1 rounded bg-marca-ambar px-1.5 py-0.5 text-[10px] font-bold text-amber-950">
            <Star className="h-3 w-3" aria-hidden="true" />
            Portada
        </span>
    );
}

function BotonMiniatura({
    etiqueta,
    onClick,
    children,
}: {
    etiqueta: string;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={etiqueta}
            title={etiqueta}
            className="rounded-md bg-white/90 p-1.5 text-gray-700 shadow-sm transition hover:bg-white hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
        >
            {children}
        </button>
    );
}
