import '../css/app.css';

import ContenedorAvisos from '@/Components/Avisos/ContenedorAvisos';
import { PageProps } from '@/types';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                {/* Avisos flotantes (KAN-319): una sola vez, por fuera de los layouts. */}
                <ContenedorAvisos
                    flashInicial={
                        props.initialPage.props.flash as PageProps['flash']
                    }
                />
            </>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
