import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Publicacion } from '@/types';
import { formatearPrecio } from '@/utils/formato';
import { useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock,
    LucideIcon,
    Minus,
    Plus,
    ShoppingCart,
    XCircle,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export type EstadoSolicitud = 'pendiente' | 'aceptada' | 'rechazada';

// Lo que manda PublicacionController::show() en `mi_solicitud` (la última del usuario).
export type MiSolicitud = { estado: EstadoSolicitud; created_at: string };

const MAX_MENSAJE = 2000;

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'long',
});

const AVISO_ESTADO: Record<
    EstadoSolicitud,
    { Icono: LucideIcon; titulo: string; texto: string; clases: string }
> = {
    pendiente: {
        Icono: Clock,
        titulo: 'Solicitud enviada',
        texto: 'Estás esperando la respuesta del productor. Cuando la acepte o la rechace, lo vas a ver acá.',
        clases: 'bg-amber-50 text-amber-900',
    },
    aceptada: {
        Icono: CheckCircle2,
        titulo: '¡El productor aceptó tu solicitud!',
        texto: 'Coordiná con él la entrega y el pago usando sus datos de contacto.',
        clases: 'bg-green-50 text-green-900',
    },
    rechazada: {
        Icono: XCircle,
        titulo: 'El productor rechazó tu solicitud',
        texto: 'Podés volver a intentarlo, por ejemplo con otra cantidad o fecha de retiro.',
        clases: 'bg-gray-50 text-gray-800',
    },
};

// Botón "Solicitar compra" + modal con la cantidad y el mensaje opcional (KAN-116/252).
// POST /publicaciones/{id}/solicitudes-compra. Si el usuario ya solicitó, muestra en qué
// quedó su última solicitud; con una pendiente no deja mandar otra (el backend tampoco).
// La cantidad va de 1 al stock disponible: el backend valida lo mismo (KAN-251) y,
// al aceptar, la descuenta del stock.
export default function SolicitarCompra({
    publicacion,
    miSolicitud,
    disponible,
}: {
    publicacion: Pick<
        Publicacion,
        'id' | 'titulo' | 'precio' | 'unidad' | 'stock'
    >;
    miSolicitud: MiSolicitud | null;
    // false si la publicación está pausada o vendida: se ve el estado, pero no se puede pedir.
    disponible: boolean;
}) {
    const [abierto, setAbierto] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({ cantidad: 1, mensaje: '' });

    const maximo = Math.max(1, publicacion.stock);
    const subtotal = Number(publicacion.precio) * data.cantidad;

    // 'publicacion' no es un campo del formulario: lo agrega StoreSolicitudCompraRequest::withValidator.
    const errorGeneral = (errors as Record<string, string | undefined>)
        .publicacion;

    const cerrar = () => {
        setAbierto(false);
        reset();
        clearErrors();
    };

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('solicitudes-compra.store', publicacion.id), {
            preserveScroll: true,
            // El mensaje de éxito llega como flash y lo muestra la página.
            onSuccess: () => cerrar(),
        });
    };

    const aviso = miSolicitud ? AVISO_ESTADO[miSolicitud.estado] : null;
    // Con una pendiente se espera la respuesta; aceptada o rechazada, se puede pedir de nuevo.
    const puedeSolicitar = disponible && miSolicitud?.estado !== 'pendiente';

    return (
        <div className="space-y-3">
            {miSolicitud && aviso && (
                <div
                    role="status"
                    className={`flex gap-2.5 rounded-md p-3 text-sm ${aviso.clases}`}
                >
                    <aviso.Icono
                        className="mt-0.5 h-4 w-4 shrink-0"
                        aria-hidden="true"
                    />
                    <div>
                        <p className="font-medium">{aviso.titulo}</p>
                        <p className="mt-0.5 opacity-90">{aviso.texto}</p>
                        <p className="mt-1 text-xs opacity-75">
                            La enviaste el{' '}
                            {formatoFecha.format(
                                new Date(miSolicitud.created_at),
                            )}
                            .
                        </p>
                    </div>
                </div>
            )}

            {puedeSolicitar && (
                <PrimaryButton
                    onClick={() => setAbierto(true)}
                    className="w-full justify-center gap-2"
                >
                    <ShoppingCart className="h-4 w-4" aria-hidden="true" />
                    {miSolicitud ? 'Hacer otra solicitud' : 'Solicitar compra'}
                </PrimaryButton>
            )}

            <Modal show={abierto} onClose={cerrar}>
                <form onSubmit={enviar} className="space-y-5 p-6">
                    <div>
                        <h2 className="text-lg font-medium text-gray-900">
                            Solicitar compra
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            El productor va a ver tu solicitud y la puede
                            aceptar o rechazar. El pago y la entrega los
                            coordinan entre ustedes.
                        </p>
                    </div>

                    {/* Resumen de lo que está pidiendo */}
                    <div className="rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                        <p className="font-medium text-gray-900">
                            {publicacion.titulo}
                        </p>
                        <p className="text-gray-600">
                            {formatearPrecio(publicacion.precio)} por{' '}
                            {publicacion.unidad}
                        </p>
                    </div>

                    <div>
                        <InputLabel htmlFor="cantidad" value="Cantidad" />
                        <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-2">
                            <SelectorCantidad
                                valor={data.cantidad}
                                maximo={maximo}
                                unidad={publicacion.unidad}
                                onCambiar={(valor) =>
                                    setData('cantidad', valor)
                                }
                            />
                            <span className="text-sm text-gray-500">
                                Disponibles:{' '}
                                {publicacion.stock.toLocaleString('es-AR')}{' '}
                                {publicacion.unidad}
                            </span>
                        </div>
                        <InputError
                            className="mt-2"
                            message={errors.cantidad}
                        />

                        {/* Referencia: el precio final lo acuerdan entre ustedes. */}
                        <p
                            aria-live="polite"
                            className="mt-3 flex items-baseline justify-between rounded-md bg-sky-50 px-3 py-2 text-sm text-sky-900"
                        >
                            <span>Subtotal estimado</span>
                            <span className="text-base font-semibold">
                                {formatearPrecio(subtotal)}
                            </span>
                        </p>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="mensaje"
                            value="Mensaje para el productor (opcional)"
                        />
                        <textarea
                            id="mensaje"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={4}
                            value={data.mensaje}
                            onChange={(e) => setData('mensaje', e.target.value)}
                            maxLength={MAX_MENSAJE}
                            placeholder="Por ejemplo: ¿puedo retirar el sábado a la mañana?"
                        />
                        <div className="mt-1 flex justify-between gap-4">
                            <InputError message={errors.mensaje} />
                            <span className="ms-auto text-xs text-gray-500">
                                {data.mensaje.length}/{MAX_MENSAJE}
                            </span>
                        </div>
                    </div>

                    {errorGeneral && (
                        <p
                            role="alert"
                            className="rounded-md bg-red-50 p-3 text-sm text-red-800"
                        >
                            {errorGeneral}
                        </p>
                    )}

                    <div className="flex justify-end gap-3">
                        <SecondaryButton onClick={cerrar}>
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton disabled={processing}>
                            Enviar solicitud
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </div>
    );
}

// Cantidad con botones − / + y un campo que también se puede escribir (KAN-252).
// Mientras se escribe se permite dejarlo vacío; al salir del campo se acomoda
// entre 1 y el máximo para que nunca quede un valor inválido.
function SelectorCantidad({
    valor,
    maximo,
    unidad,
    onCambiar,
}: {
    valor: number;
    maximo: number;
    unidad: string;
    onCambiar: (valor: number) => void;
}) {
    const [texto, setTexto] = useState(String(valor));

    const fijar = (nuevo: number) => {
        const acotado = Math.min(maximo, Math.max(1, nuevo));
        onCambiar(acotado);
        setTexto(String(acotado));
    };

    const claseBoton =
        'flex h-10 w-10 items-center justify-center text-gray-600 transition-colors hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 active:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-40';

    return (
        <div className="inline-flex items-center overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm">
            <button
                type="button"
                onClick={() => fijar(valor - 1)}
                disabled={valor <= 1}
                aria-label="Restar uno"
                className={claseBoton}
            >
                <Minus className="h-4 w-4" aria-hidden="true" />
            </button>
            <input
                id="cantidad"
                type="number"
                inputMode="numeric"
                min={1}
                max={maximo}
                value={texto}
                onChange={(e) => {
                    setTexto(e.target.value);
                    const n = parseInt(e.target.value, 10);
                    if (!Number.isNaN(n)) {
                        onCambiar(Math.min(maximo, Math.max(1, n)));
                    }
                }}
                onBlur={() => fijar(parseInt(texto, 10) || 1)}
                className="h-10 w-16 border-0 border-x border-gray-300 text-center [appearance:textfield] focus:ring-2 focus:ring-inset focus:ring-indigo-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
            />
            <span className="px-3 text-sm text-gray-600">{unidad}</span>
            <button
                type="button"
                onClick={() => fijar(valor + 1)}
                disabled={valor >= maximo}
                aria-label="Sumar uno"
                className={`${claseBoton} border-l border-gray-300`}
            >
                <Plus className="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    );
}
