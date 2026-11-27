import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Categoria, LimitesFotos, PublicacionFoto } from '@/types';
import { UNIDADES_SUGERIDAS } from '@/utils/unidades';
import { FormEventHandler } from 'react';
import CampoFotos from './CampoFotos';
import ReferenciaDePrecio from './ReferenciaDePrecio';
import usePrecioSugerido from './usePrecioSugerido';

// Campos del formulario de publicación, compartidos por Create y Edit.
// Los valores de texto van como string porque es lo que devuelven los inputs;
// Laravel los valida como numéricos/enteros del lado del servidor.
// Con archivos en "fotos", useForm manda el formulario como multipart/form-data.
export type PublicacionFormData = {
    categoria_id: string;
    titulo: string;
    descripcion: string;
    precio: string;
    unidad: string;
    stock: string;
    fotos: File[]; // fotos nuevas, ya achicadas (KAN-205)
    fotos_eliminar: number[]; // ids de fotos guardadas a quitar (solo en Edit)
};

// Mismas clases que TextInput, para el <select> y el <textarea>.
const CLASES_CAMPO =
    'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function PublicacionForm({
    data,
    setData,
    errors,
    processing,
    categorias,
    onSubmit,
    textoBoton,
    fotosExistentes = [],
    limitesFotos,
}: {
    data: PublicacionFormData;
    setData: <K extends keyof PublicacionFormData>(
        campo: K,
        valor: PublicacionFormData[K],
    ) => void;
    // Record<string, ...> porque los errores de cada foto llegan como "fotos.0", "fotos.1"...
    errors: Partial<Record<string, string>>;
    processing: boolean;
    categorias: Pick<Categoria, 'id' | 'nombre'>[];
    onSubmit: FormEventHandler;
    textoBoton: string;
    fotosExistentes?: PublicacionFoto[];
    // Configurables desde el panel admin (KAN-225); los manda el backend.
    limitesFotos: LimitesFotos;
}) {
    // Se pide antes del return temprano: los hooks no pueden ir después de un return.
    const precios = usePrecioSugerido(
        data.categoria_id,
        data.unidad,
        data.titulo,
    );
    const nombreCategoria = categorias.find(
        (categoria) => String(categoria.id) === data.categoria_id,
    )?.nombre;

    if (categorias.length === 0) {
        return (
            <p className="text-sm text-gray-600">
                Todavía no hay categorías cargadas, así que no se puede
                publicar. Pedile a un administrador que cree al menos una.
            </p>
        );
    }

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div>
                <InputLabel htmlFor="categoria_id" value="Categoría" />

                <select
                    id="categoria_id"
                    className={CLASES_CAMPO}
                    value={data.categoria_id}
                    onChange={(e) => setData('categoria_id', e.target.value)}
                    required
                >
                    <option value="" disabled>
                        Elegí una categoría
                    </option>
                    {categorias.map((categoria) => (
                        <option key={categoria.id} value={categoria.id}>
                            {categoria.nombre}
                        </option>
                    ))}
                </select>

                <InputError className="mt-2" message={errors.categoria_id} />
            </div>

            <div>
                <InputLabel htmlFor="titulo" value="Título" />

                <TextInput
                    id="titulo"
                    className="mt-1 block w-full"
                    value={data.titulo}
                    onChange={(e) => setData('titulo', e.target.value)}
                    placeholder="Ej: Tomate perita de estación"
                    maxLength={255}
                    required
                    isFocused
                />

                <InputError className="mt-2" message={errors.titulo} />
            </div>

            <div>
                <InputLabel htmlFor="descripcion" value="Descripción" />

                <textarea
                    id="descripcion"
                    className={CLASES_CAMPO}
                    rows={5}
                    value={data.descripcion}
                    onChange={(e) => setData('descripcion', e.target.value)}
                    placeholder="Contá cómo es el producto, cómo se produce, cómo se entrega…"
                    maxLength={5000}
                    required
                />

                <InputError className="mt-2" message={errors.descripcion} />
            </div>

            <div className="grid gap-6 sm:grid-cols-3">
                <div>
                    <InputLabel htmlFor="precio" value="Precio ($)" />

                    <TextInput
                        id="precio"
                        type="number"
                        inputMode="decimal"
                        min="0"
                        max="9999999.99"
                        step="0.01"
                        className="mt-1 block w-full"
                        value={data.precio}
                        onChange={(e) => setData('precio', e.target.value)}
                        aria-describedby="precio-referencia"
                        required
                    />

                    {/* Las referencias se muestran en un recuadro debajo (KAN-340). */}
                    {data.categoria_id && !data.unidad.trim() && (
                        <p className="mt-1 text-xs text-gray-600">
                            Completá la unidad para ver precios de referencia.
                        </p>
                    )}

                    <InputError className="mt-2" message={errors.precio} />
                </div>

                <div>
                    <InputLabel htmlFor="unidad" value="Unidad" />

                    <TextInput
                        id="unidad"
                        list="unidades-sugeridas"
                        className="mt-1 block w-full"
                        value={data.unidad}
                        onChange={(e) => setData('unidad', e.target.value)}
                        placeholder="Ej: kg"
                        maxLength={50}
                        required
                    />
                    <datalist id="unidades-sugeridas">
                        {UNIDADES_SUGERIDAS.map((unidad) => (
                            <option key={unidad} value={unidad} />
                        ))}
                    </datalist>

                    <InputError className="mt-2" message={errors.unidad} />
                </div>

                <div>
                    <InputLabel htmlFor="stock" value="Stock disponible" />

                    <TextInput
                        id="stock"
                        type="number"
                        inputMode="numeric"
                        min="0"
                        step="1"
                        className="mt-1 block w-full"
                        value={data.stock}
                        onChange={(e) => setData('stock', e.target.value)}
                        required
                    />

                    <InputError className="mt-2" message={errors.stock} />
                </div>
            </div>

            {/* Precio de referencia del Consorcio y rango del marketplace (KAN-340). */}
            <ReferenciaDePrecio
                precios={precios}
                unidad={data.unidad}
                precio={data.precio}
                nombreCategoria={nombreCategoria}
            />

            <CampoFotos
                existentes={fotosExistentes}
                eliminar={data.fotos_eliminar}
                onEliminarChange={(ids) => setData('fotos_eliminar', ids)}
                nuevas={data.fotos}
                onNuevasChange={(archivos) => setData('fotos', archivos)}
                // "fotos" (cantidad total) y "fotos.N" (tamaño o formato de cada una)
                errores={Object.entries(errors)
                    .filter(([campo]) => campo.startsWith('fotos'))
                    .map(([, mensaje]) => mensaje as string)}
                maximo={limitesFotos.maximo}
                tamanoMaxKb={limitesFotos.tamano_max_kb}
            />

            <div className="flex justify-end">
                <PrimaryButton disabled={processing}>
                    {textoBoton}
                </PrimaryButton>
            </div>
        </form>
    );
}
