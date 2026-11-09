import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Categoria, PageProps } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PublicacionForm, {
    PublicacionFormData,
} from './Partials/PublicacionForm';

export default function Create({
    categorias,
}: PageProps<{ categorias: Pick<Categoria, 'id' | 'nombre'>[] }>) {
    const { data, setData, post, processing, errors } =
        useForm<PublicacionFormData>({
            categoria_id: '',
            titulo: '',
            descripcion: '',
            precio: '',
            unidad: '',
            stock: '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('publicaciones.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nueva publicación
                </h2>
            }
        >
            <Head title="Nueva publicación" />

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
                            textoBoton="Publicar"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
