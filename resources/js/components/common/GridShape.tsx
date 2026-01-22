export default function GridShape() {
    return (
        <div
            aria-hidden="true"
            className="pointer-events-none absolute inset-0 z-[-1] overflow-hidden"
        >
            <svg
                className="absolute top-0 left-1/2 h-[120%] w-[120%] -translate-x-1/2 opacity-30"
                viewBox="0 0 1200 900"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <pattern
                        id="tailadmin-grid"
                        width="40"
                        height="40"
                        patternUnits="userSpaceOnUse"
                    >
                        <path
                            d="M 40 0 L 0 0 0 40"
                            fill="none"
                            stroke="currentColor"
                            strokeOpacity="0.25"
                            strokeWidth="1"
                        />
                    </pattern>
                    <radialGradient
                        id="tailadmin-fade"
                        cx="0"
                        cy="0"
                        r="1"
                        gradientUnits="userSpaceOnUse"
                        gradientTransform="translate(600 300) rotate(90) scale(520 700)"
                    >
                        <stop stopColor="white" stopOpacity="0.18" />
                        <stop offset="1" stopColor="white" stopOpacity="0" />
                    </radialGradient>
                </defs>

                <rect
                    width="1200"
                    height="900"
                    fill="url(#tailadmin-grid)"
                    className="text-white"
                />
                <rect width="1200" height="900" fill="url(#tailadmin-fade)" />
            </svg>
        </div>
    );
}
