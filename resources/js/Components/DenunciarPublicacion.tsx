import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { EstadoDenuncia } from '@/types';
import { useForm } from '@inertiajs/react';
import { Clock, Flag, LucideIcon, ShieldCheck } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

// Motivos fijos: así en el panel admin las denuncias quedan parejas y se pueden comparar.
// Se guarda el texto tal cual (el backend acepta cualquier string de hasta 255).
const MOTIVOS = [
    'Posible estafa',
    'Información falsa o engañosa',
    'Precio abusivo',
    'Contenido inapropiado',
    'Otro',
];
const MOTIVO_OTRO = 'Otro';
const MAX_DESCRIPCION = 2000;

// Mismas clases que TextInput, para el <select> y el <textarea>.
const CLASES_CAMPO =
    'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500';

// Lo que manda PublicacionController::show() en `mi_denuncia`.
export type MiDenuncia = { estado: EstadoDenuncia; created_at: string };

const formatoFecha = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric',
    month: 'long',
});

// Mensaje según en qué quedó la denuncia. Al resolverse, el backend además
// le manda una alerta al denunciante (DenunciaController::update).
const AVISO_ESTADO: Record<
    EstadoDenuncia,
    { Icono: LucideIcon; titulo: string; texto: string; clases: string }
> = {
    pendiente: {
        Icono: Clock,
        titulo: 'Tu denuncia está en revisión',
        texto: 'Un administrador la va a revisar pronto. Cuando haya novedades te avisamos en tus alertas.',
        clases: 'bg-amber-50 text-amber-900',
    },
    revisada: {
        Icono: ShieldCheck,
        titulo: 'Tu denuncia ya fue revisada',
        texto: 'Un administrador ya la revisó. ¡Gracias por ayudar a cuidar la comunidad!',
        clases: 'bg-green-50 text-green-900',
    },
    descartada: {
        Icono: ShieldCheck,
        titulo: 'Tu denuncia ya fue revisada',
        texto: 'Un administrador la revisó y no encontró problemas en esta publicación. Igual, gracias por avisar.',
        clases: 'bg-gray-50 text-gray-800',
    },
};

// Link "Denunciar publicación" + modal con el formulario (KAN-65).
// POST /publicaciones/{id}/denuncias. La página decide si mostrarlo (no al dueño);
// el backend igual rechaza la auto-denuncia y la denuncia repetida en `errors.publicacion`.
// Si el usuario ya denunció, en lugar del link muestra en qué quedó su denuncia.
export default function DenunciarPublicacion({
    publicacionId,
    miDenuncia,
}: {
    publicacionId: number;
    miDenuncia: MiDenuncia | null;
}) {
    const [abierto, setAbierto] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            motivo: '',
            descripcion: '',
        });

    // 'publicacion' no es un campo del formulario: lo agrega StoreDenunciaRequest::withValidator.
    const errorGeneral = (errors as Record<string, string | undefined>)
        .publicacion;

    // Con "Otro" la lista no dice nada, así que pedimos que lo cuente.
    const esOtro = data.motivo === MOTIVO_OTRO;

    const cerrar = () => {
        setAbierto(false);
        reset();
        clearErrors();
    };

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('publicaciones.denuncias.store', publicacionId), {
            preserveScroll: true,
            // El mensaje de éxito llega como flash y lo muestran los avisos flotantes (KAN-319).
            onSuccess: () => cerrar(),
        });
    };

    if (miDenuncia) {
        const { Icono, titulo, texto, clases } =
            AVISO_ESTADO[miDenuncia.estado];

        return (
            <div
                role="status"
                className={`flex gap-2.5 rounded-md p-3 text-sm ${clases}`}
            >
                <Icono className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                <div>
                    <p className="font-medium">{titulo}</p>
                    <p className="mt-0.5 opacity-90">{texto}</p>
                    <p className="mt-1 text-xs opacity-75">
                        La enviaste el{' '}
                        {formatoFecha.format(new Date(miDenuncia.created_at))}.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <>
            <button
                type="button"
                onClick={() => setAbierto(true)}
                className="inline-flex items-center gap-1.5 rounded text-sm text-gray-500 transition hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
            >
                <Flag className="h-4 w-4" aria-hidden="true" />
                Denunciar publicación
            </button>

            <Modal show={abierto} onClose={cerrar}>
                <form onSubmit={enviar} className="space-y-5 p-6">
                    <div>
                        <h2 className="text-lg font-medium text-gray-900">
                            Denunciar publicación
                        </h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Un administrador va a revisar tu denuncia. Tus datos
                            no se le muestran al productor.
                        </p>
                    </div>

                    <div>
                        <InputLabel htmlFor="motivo" value="Motivo" />
                        <select
                            id="motivo"
                            className={CLASES_CAMPO}
                            value={data.motivo}
                            onChange={(e) => setData('motivo', e.target.value)}
                            required
                        >
                            <option value="" disabled>
                                Elegí un motivo
                            </option>
                            {MOTIVOS.map((motivo) => (
                                <option key={motivo} value={motivo}>
                                    {motivo}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.motivo} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="descripcion"
                            value={
                                esOtro
                                    ? 'Contanos qué pasa'
                                    : 'Detalles (opcional)'
                            }
                        />
                        <textarea
                            id="descripcion"
                            className={CLASES_CAMPO}
                            rows={4}
                            value={data.descripcion}
                            onChange={(e) =>
                                setData('descripcion', e.target.value)
                            }
                            maxLength={MAX_DESCRIPCION}
                            required={esOtro}
                            placeholder="Por ejemplo: el teléfono no corresponde al productor, o pide pagar por adelantado."
                        />
                        <div className="mt-1 flex justify-between gap-4">
                            <InputError message={errors.descripcion} />
                            <span className="ms-auto text-xs text-gray-500">
                                {data.descripcion.length}/{MAX_DESCRIPCION}
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
                        <DangerButton disabled={processing}>
                            Enviar denuncia
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </>
    );
}
