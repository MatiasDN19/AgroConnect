import { avisar } from '@/Components/Avisos/avisos';
import {
    Bloque,
    BotonGuardar,
    Campo,
    CLASES_CAMPO,
} from '@/Components/Formulario';
import { numeroWhatsapp } from '@/utils/whatsapp';
import { Link, useForm, usePage } from '@inertiajs/react';
import { UserRound } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, isDirty } = useForm({
        name: user.name,
        email: user.email,
        telefono: user.telefono ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'), {
            preserveScroll: true,
            onSuccess: () => avisar('exito', 'Guardamos tus datos.'),
        });
    };

    // Mismo armado que usa el botón de WhatsApp (KAN-178), para avisar en vivo
    // si con este número los compradores van a poder escribirle.
    const ayudaTelefono = !data.telefono ? (
        'Celular con código de área, sin 0 ni 15. Ej: 3718 123456.'
    ) : numeroWhatsapp(data.telefono) ? (
        <span className="text-green-700">
            ✓ Te van a poder escribir por WhatsApp desde tus publicaciones.
        </span>
    ) : (
        <span className="text-amber-700">
            No lo reconocemos como celular argentino: revisá que tenga el código
            de área. Sin eso, no vas a tener botón de WhatsApp.
        </span>
    );

    return (
        <Bloque
            Icono={UserRound}
            titulo="Datos de la cuenta"
            descripcion="Tu nombre, tu correo y tu teléfono de contacto."
        >
            <form onSubmit={submit} className="space-y-4">
                <Campo id="name" etiqueta="Nombre" error={errors.name}>
                    <input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        autoComplete="name"
                        className={CLASES_CAMPO}
                    />
                </Campo>

                <Campo
                    id="email"
                    etiqueta="Correo electrónico"
                    error={errors.email}
                >
                    <input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                        className={CLASES_CAMPO}
                    />
                </Campo>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div className="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                        Todavía no verificaste tu correo.{' '}
                        <Link
                            href={route('verification.send')}
                            method="post"
                            as="button"
                            className="font-semibold underline underline-offset-2 hover:text-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600"
                        >
                            Reenviar el correo de verificación
                        </Link>
                        {status === 'verification-link-sent' && (
                            <p className="mt-1 font-medium text-green-700">
                                Te mandamos un nuevo enlace de verificación a tu
                                correo.
                            </p>
                        )}
                    </div>
                )}

                <Campo
                    id="telefono"
                    etiqueta="Teléfono"
                    error={errors.telefono}
                    ayuda={ayudaTelefono}
                >
                    <input
                        id="telefono"
                        type="tel"
                        value={data.telefono}
                        onChange={(e) => setData('telefono', e.target.value)}
                        autoComplete="tel"
                        placeholder="Ej: +54 9 3718 123456"
                        className={CLASES_CAMPO}
                    />
                </Campo>

                <div className="flex justify-end pt-1">
                    <BotonGuardar disabled={processing || !isDirty}>
                        Guardar cambios
                    </BotonGuardar>
                </div>
            </form>
        </Bloque>
    );
}
