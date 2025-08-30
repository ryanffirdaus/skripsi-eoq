import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { Head, Link } from '@inertiajs/react';
import React from 'react';

interface FormTemplateProps {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    backUrl: string;
    onSubmit: (e: React.FormEvent) => void;
    processing?: boolean;
    submitText?: string;
    processingText?: string;
    children: React.ReactNode;
    sections?: Array<{
        title?: string;
        children: React.ReactNode;
        className?: string;
    }>;
    className?: string;
}

export default function FormTemplate({
    title,
    breadcrumbs,
    backUrl,
    onSubmit,
    processing = false,
    submitText = 'Save',
    processingText = 'Saving...',
    children,
    sections,
    className,
}: FormTemplateProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                {/* Header */}
                <div className="mb-4 flex items-center justify-between">
                    <h1 className={cn(colors.text.primary, 'text-2xl font-bold')}>{title}</h1>
                    <Link href={backUrl}>
                        <Button
                            variant="outline"
                            className={cn('flex items-center gap-2', colors.background.primary, colors.border.primary, colors.hover.primary)}
                        >
                            <XMarkIcon className="h-4 w-4" />
                            <span>Cancel</span>
                        </Button>
                    </Link>
                </div>

                {/* Form Card */}
                <div className={cn(colors.card.base, className)}>
                    <div className={colors.card.body}>
                        <form onSubmit={onSubmit} className="space-y-6">
                            {/* Main Content */}
                            {children}

                            {/* Dynamic Sections */}
                            {sections &&
                                sections.map((section, index) => (
                                    <div key={index} className={cn('border-t pt-6', colors.border.primary, section.className)}>
                                        {section.title && <h3 className={cn(colors.text.primary, 'mb-4 text-lg font-medium')}>{section.title}</h3>}
                                        {section.children}
                                    </div>
                                ))}

                            {/* Action Buttons */}
                            <div className={cn('flex justify-end gap-3 border-t pt-6', colors.border.primary)}>
                                <Link href={backUrl}>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className={cn(
                                            colors.background.primary,
                                            colors.border.secondary,
                                            colors.text.secondary,
                                            colors.hover.primary,
                                        )}
                                    >
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing} className="flex items-center gap-2">
                                    <CheckIcon className="h-4 w-4" />
                                    <span>{processing ? processingText : submitText}</span>
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
