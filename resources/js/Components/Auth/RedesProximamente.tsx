// Botones de Google, Facebook y Apple (KAN-231). Todavía NO funcionan: el backend
// no tiene ingreso con redes sociales. Quedan visibles pero deshabilitados y con
// "Próximamente", para conectarlos en una tarea aparte sin rediseñar la pantalla.
const REDES = [
    {
        nombre: 'Google',
        icono: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                <path
                    fill="#4285F4"
                    d="M22.6 12.2c0-.7-.1-1.4-.2-2.1H12v4h6a5.1 5.1 0 0 1-2.2 3.4v2.8h3.6c2.1-1.9 3.2-4.8 3.2-8.1Z"
                />
                <path
                    fill="#34A853"
                    d="M12 23c3 0 5.5-1 7.4-2.7l-3.6-2.8c-1 .7-2.3 1.1-3.8 1.1-2.9 0-5.4-2-6.3-4.6H2v2.9A11 11 0 0 0 12 23Z"
                />
                <path
                    fill="#FBBC05"
                    d="M5.7 14a6.6 6.6 0 0 1 0-4.1V7H2a11 11 0 0 0 0 9.9L5.7 14Z"
                />
                <path
                    fill="#EA4335"
                    d="M12 5.4c1.6 0 3.1.6 4.2 1.7l3.2-3.2A11 11 0 0 0 2 7l3.7 2.9C6.6 7.3 9.1 5.4 12 5.4Z"
                />
            </svg>
        ),
    },
    {
        nombre: 'Facebook',
        icono: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                <path
                    fill="#1877F2"
                    d="M24 12a12 12 0 1 0-13.9 11.9v-8.4h-3V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12Z"
                />
            </svg>
        ),
    },
    {
        nombre: 'Apple',
        icono: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                <path
                    fill="#111827"
                    d="M16.4 12.6c0-2.6 2.1-3.8 2.2-3.9a4.8 4.8 0 0 0-3.8-2c-1.6-.2-3.1.9-3.9.9-.8 0-2-.9-3.4-.9a5 5 0 0 0-4.2 2.6c-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8 1.6 0 2 .8 3.4.8 1.4 0 2.3-1.3 3.1-2.5a11 11 0 0 0 1.4-2.9c-.1 0-2.6-1-2.6-4ZM13.8 5c.7-.8 1.2-2 1.1-3.1-1 0-2.2.7-3 1.5-.6.7-1.2 1.9-1 3 1.1.1 2.2-.6 2.9-1.4Z"
                />
            </svg>
        ),
    },
];

export default function RedesProximamente({ accion }: { accion: string }) {
    return (
        <div className="space-y-2.5">
            <div className="flex items-center gap-3 text-xs text-slate-400">
                <span className="h-px flex-1 bg-slate-200" />O {accion} con tus
                redes · próximamente
                <span className="h-px flex-1 bg-slate-200" />
            </div>

            <div className="flex justify-center gap-3">
                {REDES.map(({ nombre, icono }) => (
                    <button
                        key={nombre}
                        type="button"
                        disabled
                        title={`${nombre}: próximamente`}
                        aria-label={`${accion} con ${nombre} (próximamente)`}
                        className="flex h-11 w-16 cursor-not-allowed items-center justify-center rounded-xl bg-slate-100 opacity-60 grayscale-[30%]"
                    >
                        {icono}
                    </button>
                ))}
            </div>
        </div>
    );
}
