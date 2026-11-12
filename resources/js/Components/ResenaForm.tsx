import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

const ETIQUETAS = ['Malo', 'Regular', 'Bueno', 'Muy bueno', 'Excelente'];
const MAX_COMENTARIO = 2000;

// Formulario para dejar una reseña (POST /publicaciones/{id}/resenas).
// La página que lo usa decide si mostrarlo (no al dueño ni a quien ya reseñó);
// el backend igual lo valida y devuelve el error en `errors.publicacion`.
export default function ResenaForm({
    publicacionId,
}: {
    publicacionId: number;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        puntuacion: 0, // 0 = todavía no eligió
        comentario: '',
    });

    // Estrella sobre la que está el mouse, para la vista previa al pasar por encima.
    const [hover, setHover] = useState(0);
    const marcadas = hover || data.puntuacion;

    // 'publicacion' no es un campo del formulario: lo agrega StoreResenaRequest::withValidator.
    const errorGeneral = (errors as Record<string, string | undefined>)
        .publicacion;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('publicaciones.resenas.store', publicacionId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <h4 className="text-base font-medium text-gray-900">
                Dejá tu reseña
            </h4>

            <fieldset>
                <legend className="block text-sm font-medium text-gray-700">
                    Puntuación
                </legend>

                <div className="mt-1 flex items-center gap-3">
                    {/* Las estrellas son radio buttons ocultos: se pueden elegir con el teclado (flechas). */}
                    <div className="flex" onMouseLeave={() => setHover(0)}>
                        {[1, 2, 3, 4, 5].map((n) => (
                            <label
                                key={n}
                                className="cursor-pointer p-0.5 text-3xl leading-none"
                                onMouseEnter={() => setHover(n)}
                            >
                                <input
                                    type="radio"
                                    name="puntuacion"
                                    value={n}
                                    checked={data.puntuacion === n}
                                    onChange={() => setData('puntuacion', n)}
                                    className="peer sr-only"
                                    required
                                />
                                <span
                                    aria-hidden="true"
                                    className={`rounded transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 ${
                                        n <= marcadas
                                            ? 'text-amber-400'
                                            : 'text-gray-300'
                                    }`}
                                >
                                    ★
                                </span>
                                <span className="sr-only">
                                    {n} {n === 1 ? 'estrella' : 'estrellas'} (
                                    {ETIQUETAS[n - 1]})
                                </span>
                            </label>
                        ))}
                    </div>

                    <span className="text-sm text-gray-600" aria-live="polite">
                        {marcadas > 0
                            ? ETIQUETAS[marcadas - 1]
                            : 'Elegí de 1 a 5'}
                    </span>
                </div>

                <InputError className="mt-2" message={errors.puntuacion} />
            </fieldset>

            <div>
                <InputLabel
                    htmlFor="comentario"
                    value="Comentario (opcional)"
                />

                <textarea
                    id="comentario"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows={3}
                    value={data.comentario}
                    onChange={(e) => setData('comentario', e.target.value)}
                    placeholder="¿Cómo fue tu experiencia con el producto y el productor?"
                    maxLength={MAX_COMENTARIO}
                />

                <div className="mt-1 flex justify-between gap-2">
                    <InputError message={errors.comentario} />
                    <span className="ms-auto text-xs text-gray-500">
                        {data.comentario.length}/{MAX_COMENTARIO}
                    </span>
                </div>
            </div>

            <InputError message={errorGeneral} />

            <PrimaryButton disabled={processing}>Publicar reseña</PrimaryButton>
        </form>
    );
}
