import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Categoria, PageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export default function Index({
    categorias,
}: PageProps<{ categorias: Categoria[] }>) {
    const { flash } = usePage().props;

    // Modal de crear/editar: si "editando" es null, el formulario crea una categoría nueva.
    const [mostrarFormulario, setMostrarFormulario] = useState(false);
    const [editando, setEditando] = useState<Categoria | null>(null);

    // Modal de confirmación de borrado: guarda la categoría que se quiere eliminar.
    const [aEliminar, setAEliminar] = useState<Categoria | null>(null);

    const formulario = useForm({
        nombre: '',
        descripcion: '',
    });

    const borrado = useForm({});

    const abrirCrear = () => {
        formulario.reset();
        formulario.clearErrors();
        setEditando(null);
        setMostrarFormulario(true);
    };

    const abrirEditar = (categoria: Categoria) => {
        formulario.clearErrors();
        formulario.setData({
            nombre: categoria.nombre,
            descripcion: categoria.descripcion ?? '',
        });
        setEditando(categoria);
        setMostrarFormulario(true);
    };

    const cerrarFormulario = () => {
        setMostrarFormulario(false);
        formulario.reset();
        formulario.clearErrors();
    };

    const guardar: FormEventHandler = (e) => {
        e.preventDefault();

        const opciones = {
            preserveScroll: true,
            onSuccess: () => cerrarFormulario(),
        };

        if (editando) {
            formulario.put(route('categorias.update', editando.id), opciones);
        } else {
            formulario.post(route('categorias.store'), opciones);
        }
    };

    const eliminar: FormEventHandler = (e) => {
        e.preventDefault();

        if (!aEliminar) return;

        borrado.delete(route('categorias.destroy', aEliminar.id), {
            preserveScroll: true,
            onFinish: () => setAEliminar(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Categorías
                    </h2>

                    <PrimaryButton onClick={abrirCrear}>
                        Nueva categoría
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Categorías" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                    {flash.success && (
                        <div className="rounded-md bg-green-50 p-4 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}

                    {flash.error && (
                        <div className="rounded-md bg-red-50 p-4 text-sm text-red-800">
                            {flash.error}
                        </div>
                    )}

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {categorias.length === 0 ? (
                            <p className="p-6 text-sm text-gray-600">
                                Todavía no hay categorías. Creá la primera con
                                el botón “Nueva categoría”.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <tr>
                                            <th className="px-6 py-3">
                                                Nombre
                                            </th>
                                            <th className="px-6 py-3">
                                                Descripción
                                            </th>
                                            <th className="px-6 py-3 text-right">
                                                Publicaciones
                                            </th>
                                            <th className="px-6 py-3 text-right">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-200 text-gray-700">
                                        {categorias.map((categoria) => {
                                            const tienePublicaciones =
                                                (categoria.publicaciones_count ??
                                                    0) > 0;

                                            return (
                                                <tr key={categoria.id}>
                                                    <td className="px-6 py-4 font-medium text-gray-900">
                                                        {categoria.nombre}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        {categoria.descripcion || (
                                                            <span className="text-gray-400">
                                                                Sin descripción
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-right">
                                                        {categoria.publicaciones_count ??
                                                            0}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-right">
                                                        <SecondaryButton
                                                            onClick={() =>
                                                                abrirEditar(
                                                                    categoria,
                                                                )
                                                            }
                                                        >
                                                            Editar
                                                        </SecondaryButton>

                                                        <DangerButton
                                                            className="ms-2"
                                                            onClick={() =>
                                                                setAEliminar(
                                                                    categoria,
                                                                )
                                                            }
                                                            disabled={
                                                                tienePublicaciones
                                                            }
                                                            title={
                                                                tienePublicaciones
                                                                    ? 'No se puede eliminar: tiene publicaciones asociadas'
                                                                    : undefined
                                                            }
                                                        >
                                                            Eliminar
                                                        </DangerButton>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <Modal show={mostrarFormulario} onClose={cerrarFormulario}>
                <form onSubmit={guardar} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        {editando ? 'Editar categoría' : 'Nueva categoría'}
                    </h2>

                    <div className="mt-6">
                        <InputLabel htmlFor="nombre" value="Nombre" />

                        <TextInput
                            id="nombre"
                            className="mt-1 block w-full"
                            value={formulario.data.nombre}
                            onChange={(e) =>
                                formulario.setData('nombre', e.target.value)
                            }
                            isFocused
                            required
                            maxLength={255}
                        />

                        <InputError
                            className="mt-2"
                            message={formulario.errors.nombre}
                        />
                    </div>

                    <div className="mt-4">
                        <InputLabel
                            htmlFor="descripcion"
                            value="Descripción (opcional)"
                        />

                        <textarea
                            id="descripcion"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows={3}
                            value={formulario.data.descripcion}
                            onChange={(e) =>
                                formulario.setData(
                                    'descripcion',
                                    e.target.value,
                                )
                            }
                            maxLength={1000}
                        />

                        <InputError
                            className="mt-2"
                            message={formulario.errors.descripcion}
                        />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={cerrarFormulario}>
                            Cancelar
                        </SecondaryButton>

                        <PrimaryButton
                            className="ms-3"
                            disabled={formulario.processing}
                        >
                            {editando ? 'Guardar cambios' : 'Crear'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={aEliminar !== null} onClose={() => setAEliminar(null)}>
                <form onSubmit={eliminar} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        ¿Eliminar la categoría “{aEliminar?.nombre}”?
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        Esta acción no se puede deshacer.
                    </p>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={() => setAEliminar(null)}>
                            Cancelar
                        </SecondaryButton>

                        <DangerButton
                            className="ms-3"
                            disabled={borrado.processing}
                        >
                            Eliminar
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
