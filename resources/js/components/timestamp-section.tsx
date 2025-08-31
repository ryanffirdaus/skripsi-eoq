import { colors } from '@/lib/colors';
import { formatDateTime } from '@/lib/formatters';
import { cn } from '@/lib/utils';

interface TimestampSectionProps {
    createdAt: string;
    updatedAt: string;
    createdBy?: string;
    updatedBy?: string;
    createdLabel?: string;
    updatedLabel?: string;
}

export default function TimestampSection({
    createdAt,
    updatedAt,
    createdBy,
    updatedBy,
    createdLabel = 'Dibuat',
    updatedLabel = 'Terakhir Diupdate',
}: TimestampSectionProps) {
    return (
        <div className="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label className={cn('text-sm font-medium', colors.label.base)}>{createdLabel}</label>
                    <p className={cn('mt-1', colors.text.secondary)}>{formatDateTime(createdAt)}</p>
                    {createdBy && <p className={cn('mt-1 text-xs', colors.text.secondary)}>oleh {createdBy}</p>}
                </div>

                <div>
                    <label className={cn('text-sm font-medium', colors.label.base)}>{updatedLabel}</label>
                    <p className={cn('mt-1', colors.text.secondary)}>{formatDateTime(updatedAt)}</p>
                    {updatedBy && <p className={cn('mt-1 text-xs', colors.text.secondary)}>oleh {updatedBy}</p>}
                </div>
            </div>
        </div>
    );
}
