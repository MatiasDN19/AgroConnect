import { avisar } from '@/Components/Avisos/avisos';
import {
    Bloque,
    BotonGuardar,
    BotonSecundario,
    Campo,
    CLASES_CAMPO,
} from '@/Components/Formulario';
import SocioBadge from '@/Components/SocioBadge';
import { useForm, usePage } from '@inertiajs/react';
import { BadgeCheck, Clock } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type EstadoSocio = 'sin_solicitar' | 'pendiente' | 'validado';

// Socio del Consorcio (KAN-130): el productor carga su número de socio y
// el admin lo valida desde el panel. Ojo: el backend resetea la validación
// cada vez que se guarda (aunque el número sea el mismo), por eso cuando ya
// está validado el campo queda bloqueado hasta que toque "Cambiar número".
export default function UpdateSocioForm() {
    const user = usePage().props.auth.user;

    const estado: EstadoSocio = !user.numero_socio
        ? 'sin_solicitar'
        : user.socio_validado
          ? 'validado'
          : 'pendiente';

    const [editando, setEditando] = useState(false);
    const bloqueado = estado === 'validado' && !editando;

    const { data, setData, patch, errors, processing, reset, clearErrors } =
        useForm({
            numero_socio: user.numero_socio ?? '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.socio.update'), {
            preserveScroll: true,
            onSuccess: () => {
                setEditando(false);
                avisar(
                    'exito',
                    'Enviamos tu número de socio. Te avisamos cuando lo validen.',
                );
            },
        });
    };

    const cancelarCambio = () => {
        setEditando(false);
        reset();
        clearErrors();
    };

    return (
        <Bloque
            Icono={BadgeCheck}
            titulo="Socio del Consorcio"
            descripcion="Si sos socio del Consorcio Productivo de Laguna Naineck, cargá tu número. Cuando un administrador lo valide, tus publicaciones van a aparecer primero en las búsquedas."
            extra={<ChipEstado estado={estado} />}
        >
            <form onSubmit={submit} className="space-y-4">
                <Campo
                    id="numero_socio"
                    etiqueta="Número de socio"
                    error={errors.numero_socio}
                    ayuda={
                        estado === 'pendiente'
                            ? 'Tu solicitud está esperando la validación de un administrador.'
                            : undefined
                    }
                >
                    <input
                        id="numero_socio"
                        value={data.numero_socio}
                        onChange={(e) =>
                            setData('numero_socio', e.target.value)
                        }
                        maxLength={50}
                        disabled={bloqueado}
                        required
                        className={`${CLASES_CAMPO} sm:max-w-xs`}
                    />
                </Campo>

                {estado === 'validado' && editando && (
                    <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                        Si cambiás tu número, vas a dejar de figurar como socio
                        validado hasta que un administrador lo vuelva a revisar.
                    </p>
                )}

                <div className="flex flex-wrap justify-end gap-3 pt-1">
                    {bloqueado ? (
                        <BotonSecundario onClick={() => setEditando(true)}>
                            Cambiar número
                        </BotonSecundario>
                    ) : (
                        <>
                            {editando && (
                                <BotonSecundario onClick={cancelarCambio}>
                                    Cancelar
                                </BotonSecundario>
                            )}
                            <BotonGuardar disabled={processing}>
                                {estado === 'sin_solicitar'
                                    ? 'Solicitar validación'
                                    : 'Guardar número'}
                            </BotonGuardar>
                        </>
                    )}
                </div>
            </form>
        </Bloque>
    );
}

function ChipEstado({ estado }: { estado: EstadoSocio }) {
    // Validado: la misma insignia que ven los demás en sus publicaciones (KAN-173).
    if (estado === 'validado') {
        return <SocioBadge />;
    }

    if (estado === 'pendiente') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                <Clock className="h-4 w-4" aria-hidden="true" />
                Pendiente de validación
            </span>
        );
    }

    return (
        <span className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
            Sin solicitar
        </span>
    );
}
