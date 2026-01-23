import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            {...props}
        >
            <rect
                x="6"
                y="6"
                width="12"
                height="36"
                rx="3"
                fill="currentColor"
            />
            <rect
                x="24"
                y="18"
                width="12"
                height="12"
                rx="3"
                fill="currentColor"
                opacity="0.85"
            />
        </svg>
    );
}
