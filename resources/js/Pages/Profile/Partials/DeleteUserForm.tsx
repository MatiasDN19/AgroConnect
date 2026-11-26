import {
    Bloque,
    BotonPeligro,
    BotonSecundario,
    Campo,
    CLASES_CAMPO,
} from '@/Components/Formulario';
import Modal from '@/Components/Modal';
import { useForm } from '@inertiajs/react';
import { Trash2, TriangleAlert } from 'lucide-react';
import { FormEventHandler, useRef, useState } from 'react';

export default function DeleteUserForm() {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <Bloque
            Icono={Trash2}
            titulo="Eliminar cuenta"
            descripcion="Se borran para siempre tus datos, tus publicaciones y todo lo asociado. No se puede deshacer."
            peligro
        >
            <p className="text-sm text-slate-600">
                Antes de hacerlo, guardate lo que quieras conservar. Si solo
                querés dejar de vender por un tiempo, podés pausar tus
                publicaciones.
            </p>

            <div className="flex justify-end">
                <BotonPeligro onClick={() => setConfirmingUserDeletion(true)}>
                    Eliminar mi cuenta
                </BotonPeligro>
            </div>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <div className="flex gap-3">
                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                            <TriangleAlert
                                className="h-5 w-5"
                                aria-hidden="true"
                            />
                        </span>
                        <div>
                            <h2 className="font-display text-lg font-bold text-marca-tinta">
                                ¿Seguro que querés eliminar tu cuenta?
                            </h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Se van a borrar para siempre tus datos y tus
                                publicaciones, y no se puede deshacer. Ingresá
                                tu contraseña para confirmar.
                            </p>
                        </div>
                    </div>

                    <div className="mt-5">
                        <Campo
                            id="password_eliminar"
                            etiqueta="Contraseña"
                            error={errors.password}
                        >
                            <input
                                id="password_eliminar"
                                type="password"
                                name="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                autoComplete="current-password"
                                autoFocus
                                className={CLASES_CAMPO}
                            />
                        </Campo>
                    </div>

                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <BotonSecundario onClick={closeModal}>
                            Cancelar
                        </BotonSecundario>
                        <BotonPeligro
                            type="submit"
                            disabled={processing || !data.password}
                        >
                            Eliminar cuenta
                        </BotonPeligro>
                    </div>
                </form>
            </Modal>
        </Bloque>
    );
}
