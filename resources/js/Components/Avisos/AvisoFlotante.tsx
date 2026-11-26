import { CircleAlert, CircleCheck, Info, X } from 'lucide-react';
import {
    PointerEvent,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';
import { Aviso, descartar, quitar, TipoAviso } from './avisos';

// Cuánto queda en pantalla cada tipo (los errores, un poco más para leerlos con calma).
const DURACION: Record<TipoAviso, number> = {
    exito: 4000,
    error: 6000,
    info: 4000,
};

// Lo que dura la animación de entrada/salida (tiene que coincidir con duration-200).
const DURACION_ANIMACION = 200;

// Cuántos píxeles hay que deslizar para descartarlo en el celu.
const UMBRAL_DESLIZAR = 80;

const ESTILO: Record<
    TipoAviso,
    { Icono: typeof Info; icono: string; etiqueta: string }
> = {
    exito: {
        Icono: CircleCheck,
        icono: 'bg-green-50 text-green-600',
        etiqueta: 'Listo',
    },
    error: {
        Icono: CircleAlert,
        icono: 'bg-red-50 text-red-600',
        etiqueta: 'Error',
    },
    info: {
        Icono: Info,
        icono: 'bg-marca-azul/10 text-marca-azul',
        etiqueta: 'Aviso',
    },
};

export default function AvisoFlotante({ aviso }: { aviso: Aviso }) {
    const { id, tipo, texto, saliendo } = aviso;
    const { Icono, icono, etiqueta } = ESTILO[tipo];

    // Arranca "afuera" y enseguida pasa a visible, así la transición corre.
    // Leer el tamaño obliga al navegador a calcular el estilo inicial antes del
    // cambio (si no, se saltea la transición). Es más confiable que esperar un
    // requestAnimationFrame, que algunos navegadores frenan.
    const caja = useRef<HTMLDivElement>(null);
    const [montado, setMontado] = useState(false);
    useLayoutEffect(() => {
        caja.current?.getBoundingClientRect();
        setMontado(true);
    }, []);

    // Cuando el almacén lo marca para irse, esperamos la animación y lo quitamos.
    useEffect(() => {
        if (!saliendo) return;
        const t = setTimeout(() => quitar(id), DURACION_ANIMACION + 20);
        return () => clearTimeout(t);
    }, [saliendo, id]);

    // --- Cierre automático con pausa ---
    const [conMouse, setConMouse] = useState(false);
    const [conFoco, setConFoco] = useState(false);
    const [arrastrando, setArrastrando] = useState(false);
    const [pestanaOculta, setPestanaOculta] = useState(
        () => typeof document !== 'undefined' && document.hidden,
    );
    const pausado = conMouse || conFoco || arrastrando || pestanaOculta;

    useEffect(() => {
        const alCambiar = () => setPestanaOculta(document.hidden);
        document.addEventListener('visibilitychange', alCambiar);
        return () =>
            document.removeEventListener('visibilitychange', alCambiar);
    }, []);

    // Guardamos el tiempo que le queda, para retomar desde ahí al sacar el mouse.
    const restante = useRef(DURACION[tipo]);
    useEffect(() => {
        if (pausado || saliendo) return;
        const inicio = Date.now();
        const t = setTimeout(() => descartar(id), restante.current);
        return () => {
            clearTimeout(t);
            restante.current -= Date.now() - inicio;
        };
    }, [pausado, saliendo, id]);

    // --- Deslizar para descartar (solo táctil) ---
    const [desplazamiento, setDesplazamiento] = useState(0);
    const inicioX = useRef(0);

    const alApoyar = (e: PointerEvent<HTMLDivElement>) => {
        if (e.pointerType === 'mouse' || saliendo) return;
        // Si tocó el botón de cerrar, que funcione como un click normal.
        if ((e.target as HTMLElement).closest('button')) return;
        inicioX.current = e.clientX;
        setArrastrando(true);
        e.currentTarget.setPointerCapture(e.pointerId);
    };

    const alMover = (e: PointerEvent<HTMLDivElement>) => {
        if (!arrastrando) return;
        setDesplazamiento(e.clientX - inicioX.current);
    };

    const alSoltar = () => {
        if (!arrastrando) return;
        setArrastrando(false);
        if (Math.abs(desplazamiento) > UMBRAL_DESLIZAR) {
            // Que siga de largo hacia el lado en que lo tiró.
            setDesplazamiento(Math.sign(desplazamiento) * window.innerWidth);
            descartar(id);
        } else {
            setDesplazamiento(0);
        }
    };

    const visible = montado && !saliendo;

    return (
        // La grilla de 1fr a 0fr hace que el alto se achique suave y los demás no salten.
        <div
            ref={caja}
            className={`grid transition-[grid-template-rows] duration-200 ease-[cubic-bezier(0.23,1,0.32,1)] motion-reduce:transition-none ${
                visible ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'
            }`}
        >
            {/* overflow-hidden para que se pueda achicar a 0; el padding deja lugar a la sombra. */}
            <div className="min-h-0 overflow-hidden">
                <div className="px-3 pb-2.5">
                    <div
                        role={tipo === 'error' ? 'alert' : 'status'}
                        onMouseEnter={() => setConMouse(true)}
                        onMouseLeave={() => setConMouse(false)}
                        onFocus={() => setConFoco(true)}
                        onBlur={() => setConFoco(false)}
                        onPointerDown={alApoyar}
                        onPointerMove={alMover}
                        onPointerUp={alSoltar}
                        onPointerCancel={alSoltar}
                        style={
                            desplazamiento !== 0
                                ? {
                                      transform: `translateX(${desplazamiento}px)`,
                                      opacity: Math.max(
                                          0,
                                          1 - Math.abs(desplazamiento) / 240,
                                      ),
                                  }
                                : undefined
                        }
                        className={`pointer-events-auto flex w-full touch-pan-y items-start gap-3 rounded-xl bg-white p-3.5 pr-2 shadow-lg shadow-slate-900/10 ring-1 ring-slate-200 sm:w-96 ${
                            arrastrando
                                ? ''
                                : 'transition-[transform,opacity] duration-200 ease-[cubic-bezier(0.23,1,0.32,1)] motion-reduce:transition-opacity'
                        } ${
                            visible
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-3 opacity-0 motion-reduce:translate-y-0'
                        }`}
                    >
                        <span
                            className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${icono}`}
                        >
                            <Icono className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <p className="min-w-0 flex-1 self-center text-sm text-marca-tinta">
                            <span className="sr-only">{etiqueta}: </span>
                            {texto}
                        </p>
                        <button
                            type="button"
                            onClick={() => descartar(id)}
                            className="shrink-0 rounded-md p-1.5 text-slate-400 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-marca-azul"
                            aria-label="Cerrar aviso"
                        >
                            <X className="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
