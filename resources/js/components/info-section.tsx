import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface InfoItemProps {
    label: string;
    value: ReactNode;
    fullWidth?: boolean;
}

interface InfoSectionProps {
    title: string;
    items: InfoItemProps[];
    columns?: 1 | 2;
}

export function InfoItem({ label, value, fullWidth = false }: InfoItemProps) {
    return (
        <div className={fullWidth ? 'col-span-full' : ''}>
            <label className={cn('mb-1 block text-sm font-medium', colors.label.base)}>{label}</label>
            <div className={cn('text-base', colors.text.primary)}>{value}</div>
        </div>
    );
}

export function InfoSection({ title, items, columns = 2 }: InfoSectionProps) {
    const gridCols = columns === 1 ? 'grid-cols-1' : 'grid-cols-1 lg:grid-cols-2';

    return (
        <div className="space-y-4">
            <h3 className="text-lg font-medium text-gray-900 dark:text-white">{title}</h3>
            <div className={cn('grid gap-6', gridCols)}>
                {items.map((item, index) => (
                    <InfoItem key={index} label={item.label} value={item.value} fullWidth={item.fullWidth} />
                ))}
            </div>
        </div>
    );
}

export function TimestampSection({
    createdAt,
    updatedAt,
    createdBy,
    updatedBy,
}: {
    createdAt: string;
    updatedAt: string;
    createdBy?: string;
    updatedBy?: string;
}) {
    const formatDate = (dateString: string) => {
        try {
            return new Date(dateString).toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch {
            return 'Invalid Date';
        }
    };

    const timestampItems = [
        {
            label: 'Dibuat',
            value: (
                <div className={colors.text.secondary}>
                    <div>{formatDate(createdAt)}</div>
                    {createdBy && <div className="text-sm text-gray-500 dark:text-gray-400">oleh {createdBy}</div>}
                </div>
            ),
        },
        {
            label: 'Terakhir Diupdate',
            value: (
                <div className={colors.text.secondary}>
                    <div>{formatDate(updatedAt)}</div>
                    {updatedBy && <div className="text-sm text-gray-500 dark:text-gray-400">oleh {updatedBy}</div>}
                </div>
            ),
        },
    ];

    return (
        <div className="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {timestampItems.map((item, index) => (
                    <InfoItem key={index} label={item.label} value={item.value} />
                ))}
            </div>
        </div>
    );
}
