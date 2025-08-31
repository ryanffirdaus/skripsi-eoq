import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ReactNode } from 'react';

interface Action {
    label: string;
    href: string;
    variant?: 'default' | 'outline' | 'destructive' | 'secondary' | 'ghost' | 'link';
}

interface ShowPageTemplateProps {
    title: string;
    pageTitle: string;
    breadcrumbs: BreadcrumbItem[];
    subtitle?: string;
    badge?: {
        label: string;
        color: string;
    };
    actions: Action[];
    children: ReactNode;
}

export default function ShowPageTemplate({ title, pageTitle, breadcrumbs, subtitle, badge, actions, children }: ShowPageTemplateProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="space-y-6">
                {/* Header */}
                <div className={colors.card.base}>
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div className="flex items-center justify-between">
                            <div className="min-w-0 flex-1">
                                <h1 className="truncate text-2xl font-semibold text-gray-900 dark:text-white">{title}</h1>
                                {subtitle && <p className={cn('mt-1', colors.text.secondary)}>{subtitle}</p>}
                                {badge && (
                                    <div className="mt-2">
                                        <span
                                            className={cn('inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium', badge.color)}
                                        >
                                            {badge.label}
                                        </span>
                                    </div>
                                )}
                            </div>
                            <div className="ml-4 flex gap-3">
                                {actions.map((action, index) => (
                                    <Link key={index} href={action.href}>
                                        <Button variant={action.variant || 'outline'}>{action.label}</Button>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="p-6">{children}</div>
                </div>
            </div>
        </AppLayout>
    );
}
