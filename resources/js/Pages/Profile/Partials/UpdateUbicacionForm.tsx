import { avisar } from '@/Components/Avisos/avisos';
import {
    Bloque,
    BotonGuardar,
    BotonSecundario,
    Campo,
    CLASES_CAMPO,
} from '@/Components/Formulario';
import { LOCALIDADES_FORMOSA } from '@/utils/localidades';
import { router, useForm, usePage } from '@inertiajs/react';
import {
    CircleCheck,
    LoaderCircle,
    LocateFixed,
    MapPin,
    ShieldCheck,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

// Ubicación del perfil (KAN-273), sin mapa (el mapa es KAN-121).
// - Localidad: texto libre con sugerencias; es lo único que ven los demás.
// - Coordenadas: las da el navegador y se guardan REDONDEADAS a 2 decimales
//   (~1 km). Alcanza para calcular "A 12 km" y, si alguna vez se filtran, no
//   permiten ubicar la casa o la chacra exacta.
// profile.update exige name y email, así que se mandan siempre sin cambios.

const redondear = (valor: number) => Math.round(valor * 100) / 100;

export default function UpdateUbicacionForm() {
    const user = usePage().props.auth.user;
    const esProductor = user.rol === 'productor';
    const tieneUbicacion = user.lat != null && user.lng != null;

    const { data, setData, patch, errors, processing, isDirty } = useForm({
        name: user.name,
        email: user.email,
        localidad: user.localidad ?? '',
    });

    const guardarLocalidad: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('profile.update'), {
            preserveScroll: true,
            onSuccess: () => avisar('exito', 'Guardamos tu localidad.'),
        });
    };

    // --- Coordenadas ---
    const [buscando, setBuscando] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const hayNavegador =
        typeof navigator !== 'undefined' && 'geolocation' in navigator;

    const guardarCoordenadas = (
        lat: number | null,
        lng: number | null,
        mensaje: string,
    ) =>
        router.patch(
            route('profile.update'),
            { name: user.name, email: user.email, lat, lng },
            {
                preserveScroll: true,
                onSuccess: () => avisar('exito', mensaje),
                onError: () =>
                    setError(
                        'No pudimos guardar tu ubicación. Probá de nuevo.',
                    ),
                onFinish: () => setBuscando(false),
            },
        );

    const usarUbicacionActual = () => {
        setError(null);
        setBuscando(true);
        navigator.geolocation.getCurrentPosition(
            ({ coords }) =>
                guardarCoordenadas(
                    redondear(coords.latitude),
                    redondear(coords.longitude),
                    'Guardamos tu ubicación.',
                ),
            (fallo) => {
                setBuscando(false);
                setError(
                    fallo.code === fallo.PERMISSION_DENIED
                        ? 'El navegador no nos dio permiso para ver tu ubicación. Podés habilitarlo desde el candado de la barra de direcciones y volver a probar.'
                        : 'No pudimos obtener tu ubicación. Revisá que el GPS o la ubicación del celu estén activados y probá de nuevo.',
                );
            },
            { enableHighAccuracy: false, timeout: 15000, maximumAge: 60000 },
        );
    };

    const quitarUbicacion = () => {
        setError(null);
        setBuscando(true);
        guardarCoordenadas(null, null, 'Quitamos tu ubicación.');
    };

    return (
        <Bloque
            Icono={MapPin}
            titulo={
                esProductor
                    ? 'Ubicación de tu chacra o punto de venta'
                    : 'Tu ubicación'
            }
            descripcion={
                esProductor
                    ? 'Así los compradores saben de dónde es tu producción y a qué distancia están.'
                    : 'Sirve para ordenar las publicaciones por cercanía y ver a cuántos km está cada productor.'
            }
        >
            <form onSubmit={guardarLocalidad} className="space-y-3">
                <Campo
                    id="localidad"
                    etiqueta="Localidad"
                    error={errors.localidad}
                    ayuda="Elegí de la lista o escribila. Es lo que ven los demás."
                >
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <input
                            id="localidad"
                            list="localidades-formosa"
                            value={data.localidad}
                            onChange={(e) =>
                                setData('localidad', e.target.value)
                            }
                            maxLength={100}
                            autoComplete="address-level2"
                            placeholder="Ej: Laguna Naineck"
                            className={`${CLASES_CAMPO} sm:flex-1`}
                        />
                        <BotonGuardar
                            disabled={processing || !isDirty}
                            className="mt-1 shrink-0"
                        >
                            Guardar localidad
                        </BotonGuardar>
                    </div>
                    <datalist id="localidades-formosa">
                        {LOCALIDADES_FORMOSA.map((nombre) => (
                            <option key={nombre} value={nombre} />
                        ))}
                    </datalist>
                </Campo>
            </form>

            <div className="border-t border-slate-100 pt-4">
                <p className="text-sm font-medium text-slate-700">
                    Ubicación para calcular distancias
                </p>

                {tieneUbicacion ? (
                    <div className="mt-2 flex flex-col gap-3 rounded-lg bg-green-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="flex items-center gap-2 text-sm font-medium text-green-800">
                            <CircleCheck
                                className="h-5 w-5 shrink-0"
                                aria-hidden="true"
                            />
                            Ubicación guardada
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <BotonSecundario
                                onClick={usarUbicacionActual}
                                disabled={buscando || !hayNavegador}
                            >
                                Actualizar
                            </BotonSecundario>
                            <BotonSecundario
                                onClick={quitarUbicacion}
                                disabled={buscando}
                                peligro
                            >
                                Quitar
                            </BotonSecundario>
                        </div>
                    </div>
                ) : hayNavegador ? (
                    <div className="mt-2">
                        <p className="text-sm text-slate-500">
                            Todavía no cargaste tu ubicación.{' '}
                            {esProductor
                                ? 'Conviene hacerlo desde la chacra o el punto de venta.'
                                : 'Usamos la del lugar donde estés ahora.'}
                        </p>
                        <BotonSecundario
                            onClick={usarUbicacionActual}
                            disabled={buscando}
                            className="mt-3 inline-flex items-center gap-2"
                        >
                            {buscando ? (
                                <LoaderCircle
                                    className="h-4 w-4 animate-spin"
                                    aria-hidden="true"
                                />
                            ) : (
                                <LocateFixed
                                    className="h-4 w-4"
                                    aria-hidden="true"
                                />
                            )}
                            {buscando
                                ? 'Buscando tu ubicación…'
                                : 'Usar mi ubicación actual'}
                        </BotonSecundario>
                    </div>
                ) : (
                    <p className="mt-2 text-sm text-slate-500">
                        Este navegador no permite obtener la ubicación.
                    </p>
                )}

                {error && (
                    <p
                        role="alert"
                        className="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900"
                    >
                        {error}
                    </p>
                )}

                <p className="mt-3 flex gap-2 text-xs text-slate-500">
                    <ShieldCheck
                        className="h-4 w-4 shrink-0 text-marca-azul"
                        aria-hidden="true"
                    />
                    Nadie ve el punto exacto: solo tu localidad y la distancia
                    aproximada. Además la guardamos redondeada, con un margen de
                    alrededor de 1 km.
                </p>
            </div>
        </Bloque>
    );
}
