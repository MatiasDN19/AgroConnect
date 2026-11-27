import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Categoria, LimitesFotos, PageProps } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import PublicacionForm, {
    PublicacionFormData,
} from './Partials/PublicacionForm';

export default function Create({
    categorias,
    limites_fotos,
}: PageProps<{
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
    limites_fotos: LimitesFotos;
}>) {
    // Se puede llegar con datos precargados en la URL, por ejemplo desde el
    // bloque del Plan Nutrir del Inicio (KAN-341):
    // /publicaciones/crear?categoria_id=1&titulo=Zapallo&unidad=kg
    const precarga = new URLSearchParams(window.location.search);
    const categoriaPrecargada = precarga.get('categoria_id') ?? '';

    const { data, setData, post, processing, errors } =
        useForm<PublicacionFormData>({
            // Solo si es una categoría que existe.
            categoria_id: categorias.some(
                (c) => String(c.id) === categoriaPrecargada,
            )
                ? categoriaPrecargada
                : '',
            titulo: (precarga.get('titulo') ?? '').slice(0, 255),
            descripcion: '',
            precio: '',
            unidad: (precarga.get('unidad') ?? '').slice(0, 50),
            stock: '',
            fotos: [],
            fotos_eliminar: [],
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // Si hay fotos, useForm lo manda solo como multipart/form-data.
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
                            limitesFotos={limites_fotos}
                            onSubmit={submit}
                            textoBoton="Publicar"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
