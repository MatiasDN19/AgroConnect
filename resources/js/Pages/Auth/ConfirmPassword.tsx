import { BotonAuth, CampoContrasena } from '@/Components/Auth/CampoAuth';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Confirmar contraseña" />

            <h1 className="font-display text-2xl font-extrabold tracking-tight">
                Confirmá tu contraseña
            </h1>
            <p className="mt-1 text-sm text-slate-500">
                Esta es una sección protegida. Antes de seguir, ingresá tu
                contraseña.
            </p>

            <form onSubmit={submit} className="mt-6 space-y-4">
                <CampoContrasena
                    id="password"
                    etiqueta="Contraseña"
                    name="password"
                    value={data.password}
                    autoComplete="current-password"
                    enfocar
                    error={errors.password}
                    onChange={(e) => setData('password', e.target.value)}
                    required
                />

                <div className="pt-2">
                    <BotonAuth disabled={processing}>Confirmar</BotonAuth>
                </div>
            </form>
        </GuestLayout>
    );
}
