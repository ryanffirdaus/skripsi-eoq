import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type React from 'react';

interface ChartCardProps {
    title: string;
    description?: string;
    children: React.ReactNode;
    action?: React.ReactNode;
}

export function ChartCard({ title, description, children, action }: ChartCardProps) {
    return (
        <Card>
            <CardHeader className="px-3 py-3 sm:px-6 sm:py-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                    <div className="min-w-0 flex-1">
                        <CardTitle className="text-base sm:text-lg">{title}</CardTitle>
                        {description && <CardDescription className="mt-1 text-xs sm:text-sm">{description}</CardDescription>}
                    </div>
                    {action && <div className="flex-shrink-0">{action}</div>}
                </div>
            </CardHeader>
            <CardContent className="overflow-x-auto px-3 py-2 sm:px-6 sm:py-4">
                <div className="min-w-full">{children}</div>
            </CardContent>
        </Card>
    );
}
