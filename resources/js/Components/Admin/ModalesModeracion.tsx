import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

// Modales de moderación de publicaciones (KAN-222), compartidos por la sección
// del panel admin y el detalle de la publicación.

type PublicacionModerada = { id: number; titulo: string };

const MAX_MOTIVO = 1000;

// Bloquear: el motivo es obligatorio (el backend lo valida) y lo ve el productor.
export function ModalBloquear({
    publicacion,
    onCerrar,
}: {
    publicacion: PublicacionModerada | null;
    onCerrar: () => void;
}) {
    const { data, setData, patch, processing, errors, reset, clearErrors } =
        useForm({ motivo: '' });

    const cerrar = () => {
        reset();
        clearErrors();
        onCerrar();
    };

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        if (!publicacion) return;

        patch(route('admin.publicaciones.bloquear', publicacion.id), {
            preserveScroll: true,
            onSuccess: () => cerrar(),
        });
    };

    return (
        <Modal
            show={publicacion !== null}
            onClose={() => !processing && cerrar()}
            maxWidth="md"
        >
            <form onSubmit={enviar} className="space-y-4 p-6">
                <div>
                    <h2 className="text-lg font-medium text-gray-900">
                        ¿Bloquear la publicación?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        “{publicacion?.titulo}” deja de aparecer en el
                        marketplace y el productor no la puede reactivar. Va a
                        ver el motivo que escribas.
                    </p>
                </div>

                <div>
                    <label
                        htmlFor="motivo-bloqueo"
                        className="block text-sm font-medium text-gray-700"
                    >
                        Motivo del bloqueo
                    </label>
                    <textarea
                        id="motivo-bloqueo"
                        rows={4}
                        value={data.motivo}
                        onChange={(e) => setData('motivo', e.target.value)}
                        maxLength={MAX_MOTIVO}
                        required
                        aria-invalid={errors.motivo ? true : undefined}
                        aria-describedby={
                            errors.motivo ? 'motivo-bloqueo-error' : undefined
                        }
                        placeholder="Por ejemplo: el precio no corresponde al producto, o la publicación tiene datos falsos."
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                    />
                    <div className="mt-1 flex justify-between gap-4">
                        <InputError
                            id="motivo-bloqueo-error"
                            message={errors.motivo}
                        />
                        <span className="ms-auto text-xs text-gray-500">
                            {data.motivo.length}/{MAX_MOTIVO}
                        </span>
                    </div>
                </div>

                <div className="flex justify-end gap-3">
                    <SecondaryButton onClick={cerrar} disabled={processing}>
                        Cancelar
                    </SecondaryButton>
                    <DangerButton disabled={processing}>
                        Bloquear publicación
                    </DangerButton>
                </div>
            </form>
        </Modal>
    );
}

// Eliminar: definitivo (también borra las fotos). Solo desde el panel de moderación:
// el backend vuelve a la página anterior, y desde el detalle esa página ya no existiría.
export function ModalEliminar({
    publicacion,
    onCerrar,
}: {
    publicacion: PublicacionModerada | null;
    onCerrar: () => void;
}) {
    const [procesando, setProcesando] = useState(false);

    const eliminar = () => {
        if (!publicacion) return;

        router.delete(route('admin.publicaciones.destroy', publicacion.id), {
            preserveScroll: true,
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                onCerrar();
            },
        });
    };

    return (
        <Modal
            show={publicacion !== null}
            onClose={() => !procesando && onCerrar()}
            maxWidth="md"
        >
            <div className="p-6">
                <h2 className="text-lg font-medium text-gray-900">
                    ¿Eliminar la publicación?
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    “{publicacion?.titulo}” se borra definitivamente, junto con
                    sus fotos. <strong>No se puede deshacer.</strong> Si solo
                    querés sacarla del marketplace, bloqueala.
                </p>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton onClick={onCerrar} disabled={procesando}>
                        Cancelar
                    </SecondaryButton>
                    <DangerButton onClick={eliminar} disabled={procesando}>
                        Sí, eliminar
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
