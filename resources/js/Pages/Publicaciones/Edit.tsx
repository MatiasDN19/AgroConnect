import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    Categoria,
    LimitesFotos,
    PageProps,
    Publicacion,
    PublicacionFoto,
} from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PublicacionForm, {
    PublicacionFormData,
} from './Partials/PublicacionForm';

export default function Edit({
    publicacion,
    categorias,
    limites_fotos,
}: PageProps<{
    publicacion: Pick<
        Publicacion,
        | 'id'
        | 'categoria_id'
        | 'titulo'
        | 'descripcion'
        | 'precio'
        | 'unidad'
        | 'stock'
    > & { fotos: PublicacionFoto[] };
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
    limites_fotos: LimitesFotos;
}>) {
    // Precargamos con los datos actuales. Los números se pasan a string
    // porque el formulario trabaja todo como texto (lo que dan los inputs).
    const { data, setData, post, processing, errors } = useForm<
        PublicacionFormData & { _method: 'put' }
    >({
        categoria_id: String(publicacion.categoria_id),
        titulo: publicacion.titulo,
        descripcion: publicacion.descripcion,
        precio: String(publicacion.precio),
        unidad: publicacion.unidad,
        stock: String(publicacion.stock),
        fotos: [],
        fotos_eliminar: [],
        // Un PUT con archivos no le llega bien a PHP: se manda POST y Laravel
        // lo toma como PUT gracias a _method ("method spoofing").
        _method: 'put',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('publicaciones.update', publicacion.id), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Editar publicación
                </h2>
            }
        >
            <Head title={`Editar: ${publicacion.titulo}`} />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <PublicacionForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            categorias={categorias}
                            limitesFotos={limites_fotos}
                            onSubmit={submit}
                            textoBoton="Guardar cambios"
                            fotosExistentes={publicacion.fotos}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
