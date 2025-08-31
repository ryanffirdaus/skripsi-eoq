/**
 * Centralized Color Palette Configuration
 * This file contains all the color classes used throughout the application
 * for consistency and easy maintenance.
 */

export const colors = {
    // Primary colors
    primary: {
        DEFAULT: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 border-blue-600',
        light: 'bg-blue-50 text-blue-600 hover:bg-blue-100 border-blue-200',
        outline: 'border-blue-600 text-blue-600 hover:bg-blue-50 focus:ring-blue-500',
        ring: 'focus:ring-blue-500 focus:border-blue-500',
    },

    // Secondary colors
    secondary: {
        DEFAULT: 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500 border-gray-600',
        light: 'bg-gray-50 text-gray-600 hover:bg-gray-100 border-gray-200',
        outline: 'border-gray-600 text-gray-600 hover:bg-gray-50 focus:ring-gray-500',
    },

    // Success colors
    success: {
        DEFAULT: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 border-green-600',
        light: 'bg-green-50 text-green-600 hover:bg-green-100 border-green-200',
        outline: 'border-green-600 text-green-600 hover:bg-green-50 focus:ring-green-500',
    },

    // Danger/Destructive colors
    danger: {
        DEFAULT: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 border-red-600',
        light: 'bg-red-50 text-red-600 hover:bg-red-100 border-red-200',
        outline: 'border-red-600 text-red-600 hover:bg-red-50 focus:ring-red-500',
    },

    // Warning colors
    warning: {
        DEFAULT: 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500 border-yellow-500',
        light: 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100 border-yellow-200',
        outline: 'border-yellow-500 text-yellow-600 hover:bg-yellow-50 focus:ring-yellow-500',
    },

    // Info colors
    info: {
        DEFAULT: 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-500 border-blue-500',
        light: 'bg-blue-50 text-blue-600 hover:bg-blue-100 border-blue-200',
        outline: 'border-blue-500 text-blue-600 hover:bg-blue-50 focus:ring-blue-500',
    },

    // Neutral/Gray scale
    neutral: {
        50: 'bg-gray-50 text-gray-900 border-gray-200',
        100: 'bg-gray-100 text-gray-900 border-gray-300',
        200: 'bg-gray-200 text-gray-900 border-gray-400',
        300: 'bg-gray-300 text-gray-900 border-gray-500',
        400: 'bg-gray-400 text-white border-gray-600',
        500: 'bg-gray-500 text-white border-gray-700',
        600: 'bg-gray-600 text-white border-gray-800',
        700: 'bg-gray-700 text-white border-gray-900',
        800: 'bg-gray-800 text-white border-gray-900',
        900: 'bg-gray-900 text-white border-gray-900',
    },

    // Form inputs
    input: {
        base: 'block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-blue-500',
        error: 'block w-full rounded-lg border border-red-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500 dark:border-red-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-red-500',
        success: 'block w-full rounded-lg border border-green-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-green-500 dark:border-green-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400 dark:focus:border-green-500',
    },

    // Labels
    label: {
        base: 'block text-sm font-medium text-gray-700 dark:text-gray-300',
        required: 'block text-sm font-medium text-gray-700 dark:text-gray-300 after:content-["*"] after:ml-0.5 after:text-red-500',
    },

    // Cards and containers
    card: {
        base: 'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900',
        header: 'border-b border-gray-200 dark:border-gray-700',
        body: 'p-6',
        footer: 'border-t border-gray-200 pt-6 dark:border-gray-700',
    },

    // Background colors
    background: {
        primary: 'bg-white dark:bg-gray-900',
        secondary: 'bg-gray-50 dark:bg-gray-800',
        tertiary: 'bg-gray-100 dark:bg-gray-700',
    },

    // Text colors
    text: {
        primary: 'text-gray-900 dark:text-white',
        secondary: 'text-gray-600 dark:text-gray-400',
        tertiary: 'text-gray-500 dark:text-gray-500',
        muted: 'text-gray-400 dark:text-gray-600',
    },

    // Border colors
    border: {
        primary: 'border-gray-200 dark:border-gray-700',
        secondary: 'border-gray-300 dark:border-gray-600',
        accent: 'border-blue-200 dark:border-blue-800',
    },

    // Hover states
    hover: {
        primary: 'hover:bg-gray-50 dark:hover:bg-gray-800',
        secondary: 'hover:bg-gray-100 dark:hover:bg-gray-700',
        accent: 'hover:bg-blue-50 dark:hover:bg-blue-900/20',
    },
} as const;

/**
 * Button variant classes
 */
export const buttonVariants = {
    primary: colors.primary.DEFAULT,
    secondary: `${colors.neutral[100]} hover:${colors.neutral[200]} dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800`,
    outline: `border ${colors.primary.outline}`,
    destructive: colors.danger.DEFAULT,
    ghost: 'hover:bg-gray-100 dark:hover:bg-gray-800',
} as const;

/**
 * Alert variant classes
 */
export const alertVariants = {
    success: 'border border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300',
    error: 'border border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300',
    warning: 'border border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    info: 'border border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
} as const;

/**
 * Utility function to get color classes
 */
export const getColorClass = (variant: keyof typeof colors, type?: string) => {
    const colorGroup = colors[variant];
    if (typeof colorGroup === 'object' && type && type in colorGroup) {
        return colorGroup[type as keyof typeof colorGroup];
    }
    if (typeof colorGroup === 'string') {
        return colorGroup;
    }
    return colors.primary.DEFAULT;
};
