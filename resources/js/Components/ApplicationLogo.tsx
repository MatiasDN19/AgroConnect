import { ImgHTMLAttributes } from 'react';
import logoAgroConnect from '../../images/logos/agroconnect.webp';

// Logo de AgroConnect (reemplaza al de Laravel que traía Breeze).
// Se usa en todos los layouts; el tamaño lo define cada uno con className (ej. "h-9 w-auto").
export default function ApplicationLogo({
    alt = 'AgroConnect',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return <img src={logoAgroConnect} alt={alt} {...props} />;
}
