import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SocioBadge from '@/Components/SocioBadge';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type EstadoSocio = 'sin_solicitar' | 'pendiente' | 'validado';

// Socio del Consorcio (KAN-130): el productor carga su número de socio y
// el admin lo valida desde el panel. Ojo: el backend resetea la validación
// cada vez que se guarda (aunque el número sea el mismo), por eso cuando ya
// está validado el campo queda bloqueado hasta que toque "Cambiar número".
export default function UpdateSocioForm({
    status,
    className = '',
}: {
    status?: string;
    className?: string;
}) {
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
            onSuccess: () => setEditando(false),
        });
    };

    const cancelarCambio = () => {
        setEditando(false);
        reset();
        clearErrors();
    };

    return (
        <section className={className}>
            <header className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-lg font-medium text-gray-900">
                        Socio del Consorcio
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        Si sos socio del Consorcio Productivo de Laguna Naineck,
                        cargá tu número de socio. Cuando un administrador lo
                        valide, tus publicaciones van a aparecer primero en las
                        búsquedas.
                    </p>
                </div>

                <ChipEstado estado={estado} />
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel
                        htmlFor="numero_socio"
                        value="Número de socio"
                    />

                    <TextInput
                        id="numero_socio"
                        className="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                        value={data.numero_socio}
                        onChange={(e) =>
                            setData('numero_socio', e.target.value)
                        }
                        maxLength={50}
                        disabled={bloqueado}
                        required
                    />

                    <InputError
                        className="mt-2"
                        message={errors.numero_socio}
                    />

                    {estado === 'pendiente' && (
                        <p className="mt-2 text-sm text-gray-600">
                            Tu solicitud está esperando la validación de un
                            administrador.
                        </p>
                    )}

                    {estado === 'validado' && editando && (
                        <p className="mt-2 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                            Si cambiás tu número, vas a dejar de figurar como
                            socio validado hasta que un administrador lo vuelva
                            a revisar.
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-4">
                    {bloqueado ? (
                        <SecondaryButton onClick={() => setEditando(true)}>
                            Cambiar número
                        </SecondaryButton>
                    ) : (
                        <>
                            <PrimaryButton disabled={processing}>
                                {estado === 'sin_solicitar'
                                    ? 'Solicitar validación'
                                    : 'Guardar número'}
                            </PrimaryButton>

                            {editando && (
                                <SecondaryButton onClick={cancelarCambio}>
                                    Cancelar
                                </SecondaryButton>
                            )}
                        </>
                    )}

                    <Transition
                        show={status === 'socio-actualizado'}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">
                            Solicitud enviada.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
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
        <span className="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
            Sin solicitar
        </span>
    );
}
