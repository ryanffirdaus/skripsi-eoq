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
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>{title}</CardTitle>
                        {description && <CardDescription className="mt-1">{description}</CardDescription>}
                    </div>
                    {action && <div>{action}</div>}
                </div>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}
