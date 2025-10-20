import { EyeIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';

export interface ActionButton<T> {
    label: string;
    icon?: React.ComponentType<{ className?: string }>;
    variant?: 'default' | 'secondary' | 'outline' | 'destructive' | 'ghost' | 'link';
    onClick: (item: T) => void;
    show?: (item: T) => boolean;
}

export function createEditAction<T>(getEditUrl: (item: T) => string, show?: (item: T) => boolean): ActionButton<T> {
    return {
        label: 'Ubah',
        icon: PencilIcon,
        variant: 'outline',
        onClick: (item) => router.visit(getEditUrl(item)),
        show,
    };
}

export function createViewAction<T>(getViewUrl: (item: T) => string, show?: (item: T) => boolean): ActionButton<T> {
    return {
        label: 'Detail',
        icon: EyeIcon,
        variant: 'ghost',
        onClick: (item) => router.visit(getViewUrl(item)),
        show,
    };
}

export function createDeleteAction<T>(onDelete: (item: T) => void, show?: (item: T) => boolean): ActionButton<T> {
    return {
        label: 'Hapus',
        icon: TrashIcon,
        variant: 'destructive',
        onClick: onDelete, // This will be intercepted by TableTemplate for confirmation
        show,
    };
}
