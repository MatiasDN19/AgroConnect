import {
    BotonAuth,
    CampoAuth,
    CampoContrasena,
} from '@/Components/Auth/CampoAuth';
import RedesProximamente from '@/Components/Auth/RedesProximamente';
import Checkbox from '@/Components/Checkbox';
import GuestLayout, { PRECARGA } from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { FormEventHandler, ReactNode } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Ingresar" />

            <h1 className="font-display text-2xl font-extrabold tracking-tight">
                Ingresá a tu cuenta
            </h1>
            <p className="mt-1 text-sm text-slate-500">
                Usá el correo y la contraseña con los que te registraste.
            </p>

            {status && (
                <div
                    role="status"
                    className="mt-4 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-800"
                >
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="mt-6 space-y-4">
                <CampoAuth
                    id="email"
                    etiqueta="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    autoComplete="username"
                    placeholder="tucorreo@ejemplo.com"
                    icono={Mail}
                    enfocar
                    error={errors.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                />

                <CampoContrasena
                    id="password"
                    etiqueta="Contraseña"
                    name="password"
                    value={data.password}
                    autoComplete="current-password"
                    error={errors.password}
                    onChange={(e) => setData('password', e.target.value)}
                    required
                />

                <div className="flex items-center justify-between gap-4">
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData(
                                    'remember',
                                    (e.target.checked || false) as false,
                                )
                            }
                        />
                        Recordarme
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="rounded text-sm font-medium text-marca-azul hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    )}
                </div>

                <div className="pt-2">
                    <BotonAuth disabled={processing}>Ingresar</BotonAuth>
                </div>
            </form>

            <div className="mt-6">
                <RedesProximamente accion="ingresar" />
            </div>

            <p className="mt-6 text-center text-sm text-slate-500">
                ¿No tenés cuenta?{' '}
                <Link
                    href={route('register')}
                    {...PRECARGA}
                    className="font-semibold text-marca-azul hover:underline"
                >
                    Registrate
                </Link>
            </p>
        </>
    );
}

// Layout persistente: no se desmonta al ir a /register y permite animar el cambio.
Login.layout = (page: ReactNode) => <GuestLayout>{page}</GuestLayout>;
