import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { CheckIcon } from '@heroicons/react/24/outline';
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
    submitText = 'Simpan',
    processingText = 'Menyimpan...',
    children,
    sections,
    className,
}: FormTemplateProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

            <div className="flex h-full flex-1 flex-col gap-2 overflow-hidden p-3 sm:gap-3 sm:p-4 md:gap-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h1 className={cn(colors.text.primary, 'text-xl font-bold sm:text-2xl')}>{title}</h1>
                </div>

                {/* Form Card */}
                <div className={cn(colors.card.base, 'overflow-hidden rounded-lg md:rounded-xl', className)}>
                    <div className={cn('p-3 sm:p-4 md:p-6', colors.card.body)}>
                        <form onSubmit={onSubmit} className="space-y-4 sm:space-y-6">
                            {/* Main Content */}
                            {children}

                            {/* Dynamic Sections */}
                            {sections &&
                                sections.map((section, index) => (
                                    <div key={index} className={cn('border-t pt-4 sm:pt-6', colors.border.primary, section.className)}>
                                        {section.title && (
                                            <h3 className={cn(colors.text.primary, 'mb-3 text-base font-medium sm:mb-4 sm:text-lg')}>
                                                {section.title}
                                            </h3>
                                        )}
                                        {section.children}
                                    </div>
                                ))}

                            {/* Action Buttons */}
                            <div
                                className={cn('flex flex-col gap-2 border-t pt-4 sm:flex-row sm:justify-end sm:gap-3 sm:pt-6', colors.border.primary)}
                            >
                                <Link href={backUrl} className="w-full sm:w-auto">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className={cn(
                                            'w-full',
                                            colors.background.primary,
                                            colors.border.secondary,
                                            colors.text.secondary,
                                            colors.hover.primary,
                                        )}
                                    >
                                        Batal
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing} className="flex w-full items-center justify-center gap-2 sm:w-auto">
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
