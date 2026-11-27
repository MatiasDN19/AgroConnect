import { Download, LoaderCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

// Botón "Exportar a CSV" del panel admin (KAN-318). Backend: KAN-305
// (ExportacionController). Es un <a href download> común y no un Link de
// Inertia, porque baja un archivo en vez de cambiar de página.
// El navegador no avisa cuándo termina la descarga: "Preparando…" se muestra
// un tiempo fijo, para que se note que se tocó.

const TIEMPO_PREPARANDO_MS = 2500;

export default function BotonExportar({
    href,
    etiqueta = 'Exportar a CSV',
    className = '',
}: {
    href: string;
    etiqueta?: string;
    className?: string;
}) {
    const [preparando, setPreparando] = useState(false);

    useEffect(() => {
        if (!preparando) return;
        const t = setTimeout(() => setPreparando(false), TIEMPO_PREPARANDO_MS);
        return () => clearTimeout(t);
    }, [preparando]);

    return (
        <a
            href={href}
            download
            onClick={(e) => {
                // Evita bajar el archivo dos veces con dos clicks seguidos.
                if (preparando) e.preventDefault();
                else setPreparando(true);
            }}
            aria-disabled={preparando}
            className={`inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 transition duration-150 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 active:scale-[0.98] aria-disabled:cursor-wait aria-disabled:opacity-70 ${className}`}
        >
            {preparando ? (
                <LoaderCircle
                    className="h-4 w-4 animate-spin"
                    aria-hidden="true"
                />
            ) : (
                <Download className="h-4 w-4" aria-hidden="true" />
            )}
            {preparando ? 'Preparando…' : etiqueta}
        </a>
    );
}
