import Desplegable from '@/Components/Desplegable';
import { Bloque, Campo, CLASES_CAMPO } from '@/Components/Formulario';
import AdminLayout from '@/Layouts/AdminLayout';
import { Configuracion } from '@/types';
import { numeroWhatsapp } from '@/utils/whatsapp';
import { useForm } from '@inertiajs/react';
import { Contact, SlidersHorizontal } from 'lucide-react';
import { FormEventHandler } from 'react';

// Configuración de la plataforma (KAN-225). Lo que se guarda acá lo usan:
// - el pie de la landing (datos de contacto, compartidos por HandleInertiaRequests);
// - el precio de referencia al publicar y las validaciones de fotos (reglas).

// El backend guarda el tamaño en KB (entre 512 y 10240); el admin elige en MB.
const TAMANOS_KB = [512, 1024, 1536, 2048, 3072, 5120, 10240];
const textoMb = (kb: number) => `${String(kb / 1024).replace('.', ',')} MB`;

export default function Edit({
    configuraciones,
}: {
    configuraciones: Configuracion;
}) {
    // Todo como texto: es lo que dan los inputs. Laravel valida tipos y rangos.
    const texto = (valor: string | number | null) =>
        valor === null ? '' : String(valor);

    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm({
            contacto_email: texto(configuraciones.contacto_email),
            contacto_whatsapp: texto(configuraciones.contacto_whatsapp),
            contacto_direccion: texto(configuraciones.contacto_direccion),
            red_facebook: texto(configuraciones.red_facebook),
            red_instagram: texto(configuraciones.red_instagram),
            precio_sugerido_minimo: texto(
                configuraciones.precio_sugerido_minimo,
            ),
            fotos_maximo: texto(configuraciones.fotos_maximo),
            fotos_tamano_max_kb: texto(configuraciones.fotos_tamano_max_kb),
        });

    const guardar: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.configuracion.update'), { preserveScroll: true });
    };

    const whatsappValido = numeroWhatsapp(data.contacto_whatsapp);

    return (
        <AdminLayout titulo="Configuración">
            <form onSubmit={guardar} className="mx-auto max-w-3xl space-y-6">
                <Bloque
                    Icono={Contact}
                    titulo="Datos de contacto"
                    descripcion="Se muestran en el pie de la página de inicio. Los que dejes vacíos no aparecen."
                >
                    <Campo
                        id="contacto_email"
                        etiqueta="Email de contacto"
                        error={errors.contacto_email}
                    >
                        <input
                            id="contacto_email"
                            type="email"
                            value={data.contacto_email}
                            onChange={(e) =>
                                setData('contacto_email', e.target.value)
                            }
                            placeholder="consorcio@ejemplo.com"
                            className={CLASES_CAMPO}
                        />
                    </Campo>

                    <Campo
                        id="contacto_whatsapp"
                        etiqueta="WhatsApp de contacto"
                        error={errors.contacto_whatsapp}
                        ayuda={
                            !data.contacto_whatsapp ? (
                                'Celular con código de área, sin 0 ni 15. Ej: 3718 123456.'
                            ) : whatsappValido ? (
                                <span className="text-green-700">
                                    ✓ Desde la página de inicio van a poder
                                    escribir por WhatsApp.
                                </span>
                            ) : (
                                <span className="text-amber-700">
                                    No lo reconocemos como celular argentino:
                                    revisá el código de área.
                                </span>
                            )
                        }
                    >
                        <input
                            id="contacto_whatsapp"
                            type="tel"
                            value={data.contacto_whatsapp}
                            onChange={(e) =>
                                setData('contacto_whatsapp', e.target.value)
                            }
                            placeholder="3718 123456"
                            className={CLASES_CAMPO}
                        />
                    </Campo>

                    <Campo
                        id="contacto_direccion"
                        etiqueta="Dirección"
                        error={errors.contacto_direccion}
                    >
                        <input
                            id="contacto_direccion"
                            value={data.contacto_direccion}
                            onChange={(e) =>
                                setData('contacto_direccion', e.target.value)
                            }
                            placeholder="Laguna Naineck, Formosa"
                            className={CLASES_CAMPO}
                        />
                    </Campo>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Campo
                            id="red_facebook"
                            etiqueta="Facebook"
                            error={errors.red_facebook}
                        >
                            <input
                                id="red_facebook"
                                type="url"
                                value={data.red_facebook}
                                onChange={(e) =>
                                    setData('red_facebook', e.target.value)
                                }
                                placeholder="https://facebook.com/…"
                                className={CLASES_CAMPO}
                            />
                        </Campo>

                        <Campo
                            id="red_instagram"
                            etiqueta="Instagram"
                            error={errors.red_instagram}
                        >
                            <input
                                id="red_instagram"
                                type="url"
                                value={data.red_instagram}
                                onChange={(e) =>
                                    setData('red_instagram', e.target.value)
                                }
                                placeholder="https://instagram.com/…"
                                className={CLASES_CAMPO}
                            />
                        </Campo>
                    </div>
                </Bloque>

                <Bloque
                    Icono={SlidersHorizontal}
                    titulo="Reglas de la plataforma"
                    descripcion="Se aplican enseguida, sin tocar el código."
                >
                    <Campo
                        id="precio_sugerido_minimo"
                        etiqueta="Publicaciones mínimas para el precio de referencia"
                        error={errors.precio_sugerido_minimo}
                        ayuda="Al publicar, se muestra el precio típico de la categoría y unidad solo si hay al menos esta cantidad de publicaciones activas para comparar. Con pocas, la referencia no es confiable."
                    >
                        <input
                            id="precio_sugerido_minimo"
                            type="number"
                            min={1}
                            max={100}
                            value={data.precio_sugerido_minimo}
                            onChange={(e) =>
                                setData(
                                    'precio_sugerido_minimo',
                                    e.target.value,
                                )
                            }
                            className={`${CLASES_CAMPO} sm:max-w-[10rem]`}
                        />
                    </Campo>

                    <Campo
                        id="fotos_maximo"
                        etiqueta="Fotos por publicación (máximo)"
                        error={errors.fotos_maximo}
                        ayuda="Cuántas fotos puede subir un productor en cada publicación, entre 1 y 10. Las publicaciones que ya tienen más fotos no se tocan."
                    >
                        <input
                            id="fotos_maximo"
                            type="number"
                            min={1}
                            max={10}
                            value={data.fotos_maximo}
                            onChange={(e) =>
                                setData('fotos_maximo', e.target.value)
                            }
                            className={`${CLASES_CAMPO} sm:max-w-[10rem]`}
                        />
                    </Campo>

                    <Campo
                        id="fotos_tamano_max_kb"
                        etiqueta="Tamaño máximo por foto"
                        error={errors.fotos_tamano_max_kb}
                        ayuda="Desde la web las fotos se achican solas antes de subirse (quedan en unos cientos de KB), así que este límite protege sobre todo a la app y la API."
                    >
                        <Desplegable
                            id="fotos_tamano_max_kb"
                            etiqueta="Tamaño máximo por foto"
                            className="mt-1 sm:max-w-[10rem]"
                            valor={data.fotos_tamano_max_kb}
                            onChange={(v) => setData('fotos_tamano_max_kb', v)}
                            opciones={[
                                // Si quedó guardado un valor que no está en la
                                // lista, se muestra igual.
                                ...(!TAMANOS_KB.includes(
                                    Number(data.fotos_tamano_max_kb),
                                ) && data.fotos_tamano_max_kb
                                    ? [Number(data.fotos_tamano_max_kb)]
                                    : []),
                                ...TAMANOS_KB,
                            ].map((kb) => ({
                                valor: String(kb),
                                etiqueta: textoMb(kb),
                            }))}
                            invalido={Boolean(errors.fotos_tamano_max_kb)}
                            aria-describedby="fotos_tamano_max_kb-error"
                        />
                    </Campo>
                </Bloque>

                <div className="flex items-center justify-end gap-4">
                    {recentlySuccessful && (
                        <p className="text-sm text-green-700">Guardado.</p>
                    )}
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-marca-azul-profundo px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-marca-azul focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2 disabled:opacity-60"
                    >
                        Guardar cambios
                    </button>
                </div>
            </form>
        </AdminLayout>
    );
}
