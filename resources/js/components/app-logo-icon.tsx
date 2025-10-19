import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return <img {...props} src="/logo_owl.png" alt="OWL Logo" />;
}
