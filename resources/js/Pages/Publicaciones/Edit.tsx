import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Categoria, PageProps, Publicacion } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PublicacionForm, {
    PublicacionFormData,
} from './Partials/PublicacionForm';

export default function Edit({
    publicacion,
    categorias,
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
    >;
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
}>) {
    // Precargamos con los datos actuales. Los números se pasan a string
    // porque el formulario trabaja todo como texto (lo que dan los inputs).
    const { data, setData, put, processing, errors } =
        useForm<PublicacionFormData>({
            categoria_id: String(publicacion.categoria_id),
            titulo: publicacion.titulo,
            descripcion: publicacion.descripcion,
            precio: String(publicacion.precio),
            unidad: publicacion.unidad,
            stock: String(publicacion.stock),
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('publicaciones.update', publicacion.id));
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
                            onSubmit={submit}
                            textoBoton="Guardar cambios"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
