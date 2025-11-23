/**
 * Responsive Tailwind CSS Classes Constants
 * Memastikan konsistensi responsive design di seluruh aplikasi
 */

export const RESPONSIVE_CLASSES = {
    // Padding & Margin
    containerPadding: 'px-3 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4',
    sectionPadding: 'p-3 sm:p-4 md:p-6',
    tightPadding: 'px-2 py-1 sm:px-3 sm:py-2 md:px-4 md:py-3',

    // Grid Layouts
    gridLayout: 'grid grid-cols-1 gap-2 sm:gap-3 md:gap-4 lg:gap-6',
    gridLayout2Col: 'grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 md:gap-4 lg:gap-6',
    gridLayout3Col: 'grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 md:grid-cols-3 md:gap-4 lg:gap-6',
    gridLayout4Col: 'grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 md:grid-cols-3 lg:grid-cols-4 lg:gap-6',

    // Flex Layouts
    flexCenter: 'flex flex-col gap-2 sm:gap-3 md:gap-4 items-center justify-center',
    flexBetween: 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3 md:gap-4',
    flexRow: 'flex flex-col sm:flex-row gap-2 sm:gap-3 md:gap-4',

    // Text Sizes
    heading1: 'text-2xl sm:text-3xl md:text-4xl font-bold',
    heading2: 'text-xl sm:text-2xl md:text-3xl font-bold',
    heading3: 'text-lg sm:text-xl md:text-2xl font-semibold',
    bodyText: 'text-sm sm:text-base md:text-base',
    smallText: 'text-xs sm:text-sm md:text-sm',

    // Button Sizes
    buttonSm: 'px-2 py-1 sm:px-3 sm:py-1.5 text-xs sm:text-sm',
    buttonMd: 'px-3 py-2 sm:px-4 sm:py-2 text-sm sm:text-base',
    buttonLg: 'px-4 py-2 sm:px-6 sm:py-3 text-sm sm:text-base',

    // Input & Form
    inputHeight: 'h-9 sm:h-10',
    formGap: 'gap-2 sm:gap-3 md:gap-4',

    // Card & Container
    cardRounded: 'rounded-lg md:rounded-xl',
    cardPadding: 'p-3 sm:p-4 md:p-6',

    // Width Constraints
    containerMax: 'max-w-7xl',
    containerMaxSmall: 'max-w-4xl',
    containerMaxLarge: 'max-w-full md:max-w-7xl',

    // Height Utilities
    minHeightScreen: 'min-h-screen',
    minHeightHalf: 'min-h-32 sm:min-h-40 md:min-h-48',
};

/**
 * Responsive breakpoint values
 */
export const BREAKPOINTS = {
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    '2xl': 1536,
};

/**
 * Mobile-first responsive helper
 */
export const responsiveValue = (mobile: string, tablet: string = mobile, desktop: string = tablet): string => {
    return `${mobile} sm:${tablet} md:${desktop}`;
};

/**
 * Commonly used responsive patterns
 */
export const RESPONSIVE_PATTERNS = {
    // Sidebar + Main Layout
    sidebarLayout: 'grid grid-cols-1 md:grid-cols-4 gap-4',
    sidebarMain: 'md:col-span-3',
    sidebarSide: 'md:col-span-1',

    // Two Column
    twoColumnLayout: 'grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6',

    // Three Column
    threeColumnLayout: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6',

    // Full Width
    fullWidth: 'w-full max-w-full',

    // Auto Width with Constraint
    autoWidth: 'w-auto max-w-full md:max-w-7xl',
};
