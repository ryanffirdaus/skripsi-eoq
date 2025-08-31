/**
 * Format currency in Indonesian Rupiah
 */
export const formatCurrency = (amount: number | null | undefined): string => {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return 'Rp 0';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

/**
 * Format number with thousand separators
 */
export const formatNumber = (num: number | null | undefined): string => {
    if (num === null || num === undefined || isNaN(num)) {
        return '0';
    }

    return new Intl.NumberFormat('id-ID').format(num);
};

/**
 * Format percentage
 */
export const formatPercentage = (value: number | null | undefined, decimals = 2): string => {
    if (value === null || value === undefined || isNaN(value)) {
        return '0%';
    }

    return `${value.toFixed(decimals)}%`;
};

/**
 * Safe division to avoid NaN
 */
export const safeDivide = (dividend: number | null | undefined, divisor: number | null | undefined): number => {
    if (!dividend || !divisor || isNaN(dividend) || isNaN(divisor) || divisor === 0) {
        return 0;
    }
    return dividend / divisor;
};

/**
 * Safe multiplication to avoid NaN
 */
export const safeMultiply = (a: number | null | undefined, b: number | null | undefined): number => {
    if (a === null || a === undefined || b === null || b === undefined || isNaN(a) || isNaN(b)) {
        return 0;
    }
    return a * b;
};

/**
 * Safe addition to avoid NaN
 */
export const safeAdd = (...values: (number | null | undefined)[]): number => {
    return values.reduce((sum: number, value) => {
        if (value === null || value === undefined || isNaN(value)) {
            return sum;
        }
        return sum + value;
    }, 0);
};

/**
 * Format date in Indonesian locale
 */
export const formatDate = (dateString: string | null | undefined, options?: Intl.DateTimeFormatOptions): string => {
    if (!dateString) {
        return '-';
    }

    try {
        const defaultOptions: Intl.DateTimeFormatOptions = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        };

        return new Date(dateString).toLocaleDateString('id-ID', options || defaultOptions);
    } catch {
        return 'Invalid Date';
    }
};

/**
 * Format date with time
 */
export const formatDateTime = (dateString: string | null | undefined): string => {
    return formatDate(dateString, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
