interface ActionButtonsProps {
    onView?: () => void;
    onEdit?: () => void;
    onDelete?: () => void;
    permissions: {
        canEdit?: boolean;
        canDelete?: boolean;
    };
    viewLabel?: string;
    editLabel?: string;
    deleteLabel?: string;
    size?: 'sm' | 'md' | 'lg';
    variant?: 'outline' | 'ghost';
}

/**
 * Component untuk menampilkan action buttons dengan permission checking
 * Tombol edit dan delete hanya akan ditampilkan jika user memiliki permission
 *
 * Usage:
 * <ActionButtons
 *   permissions={permissions}
 *   onEdit={() => navigate(`/edit/${id}`)}
 *   onDelete={() => handleDelete()}
 * />
 */
export function ActionButtons({
    onView,
    onEdit,
    onDelete,
    permissions,
    viewLabel = 'View',
    editLabel = 'Edit',
    deleteLabel = 'Delete',
    size = 'sm',
    variant = 'ghost',
}: ActionButtonsProps) {
    const baseClasses = {
        sm: 'px-2 py-1 text-xs',
        md: 'px-3 py-2 text-sm',
        lg: 'px-4 py-3 text-base',
    };

    const buttonClasses = `${baseClasses[size]} rounded transition-colors`;

    const variantClasses = {
        outline: 'border border-gray-300 hover:bg-gray-100',
        ghost: 'hover:bg-gray-100',
    };

    return (
        <div className="flex gap-2">
            {onView && (
                <button
                    onClick={onView}
                    className={`${buttonClasses} ${variantClasses[variant]} text-blue-600 hover:text-blue-800`}
                    title={viewLabel}
                >
                    {viewLabel}
                </button>
            )}

            {permissions.canEdit && onEdit && (
                <button
                    onClick={onEdit}
                    className={`${buttonClasses} ${variantClasses[variant]} text-amber-600 hover:text-amber-800`}
                    title={editLabel}
                >
                    {editLabel}
                </button>
            )}

            {permissions.canDelete && onDelete && (
                <button
                    onClick={onDelete}
                    className={`${buttonClasses} ${variantClasses[variant]} text-red-600 hover:text-red-800`}
                    title={deleteLabel}
                >
                    {deleteLabel}
                </button>
            )}
        </div>
    );
}

export default ActionButtons;
