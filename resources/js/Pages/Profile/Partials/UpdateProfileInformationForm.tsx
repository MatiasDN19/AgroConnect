import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { numeroWhatsapp } from '@/utils/whatsapp';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            telefono: user.telefono ?? '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Datos de tu cuenta
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    Actualizá tu nombre, tu correo y tu teléfono de contacto.
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="Nombre" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Correo electrónico" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                <div>
                    <InputLabel htmlFor="telefono" value="Teléfono" />

                    <TextInput
                        id="telefono"
                        type="tel"
                        className="mt-1 block w-full"
                        value={data.telefono}
                        onChange={(e) => setData('telefono', e.target.value)}
                        autoComplete="tel"
                        placeholder="Ej: +54 9 3718 123456"
                        aria-describedby="telefono-ayuda"
                    />

                    {/* Mismo armado que usa el botón de WhatsApp (KAN-178), para avisar en vivo
                        si con este número los compradores van a poder escribirle. */}
                    <p
                        id="telefono-ayuda"
                        className={`mt-1 text-xs ${
                            !data.telefono
                                ? 'text-gray-500'
                                : numeroWhatsapp(data.telefono)
                                  ? 'text-green-700'
                                  : 'text-amber-700'
                        }`}
                    >
                        {!data.telefono
                            ? 'Celular con código de área, sin 0 ni 15. Ej: 3718 123456.'
                            : numeroWhatsapp(data.telefono)
                              ? '✓ Te van a poder escribir por WhatsApp desde tus publicaciones.'
                              : 'No lo reconocemos como celular argentino: revisá que tenga el código de área. Sin eso, no vas a tener botón de WhatsApp.'}
                    </p>

                    <InputError className="mt-2" message={errors.telefono} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Todavía no verificaste tu correo.{' '}
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Reenviar el correo de verificación.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                Te mandamos un nuevo enlace de verificación a tu
                                correo.
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Guardar</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600">Guardado.</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
