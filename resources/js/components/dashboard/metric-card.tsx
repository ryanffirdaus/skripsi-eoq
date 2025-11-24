import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowDown, ArrowUp, Minus } from 'lucide-react';

interface MetricCardProps {
    title: string;
    value: string | number;
    change?: number;
    trend?: 'up' | 'down' | 'neutral';
    icon?: React.ReactNode;
    color?: string;
    subtitle?: string;
}

export function MetricCard({ title, value, change, trend = 'neutral', icon, color = 'text-blue-600', subtitle }: MetricCardProps) {
    const getTrendIcon = () => {
        switch (trend) {
            case 'up':
                return <ArrowUp className="h-4 w-4 text-green-600" />;
            case 'down':
                return <ArrowDown className="h-4 w-4 text-red-600" />;
            default:
                return <Minus className="h-4 w-4 text-gray-400" />;
        }
    };

    const getTrendColor = () => {
        switch (trend) {
            case 'up':
                return 'text-green-600';
            case 'down':
                return 'text-red-600';
            default:
                return 'text-gray-500';
        }
    };

    return (
        <Card>
            <CardHeader className="px-3 py-1.5 pb-0 sm:px-3 sm:py-2 sm:pb-0">
                <div className="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between">
                    <CardTitle className="text-xs font-medium text-gray-500 sm:text-sm dark:text-gray-400">{title}</CardTitle>
                    {icon && (
                        <div
                            className={`w-fit rounded-lg p-1.5 ${color.replace('text-', 'bg-').replace('-600', '-100')} dark:${color.replace('text-', 'bg-').replace('-600', '-900')}`}
                        >
                            {icon}
                        </div>
                    )}
                </div>
            </CardHeader>
            <CardContent className="px-3 pt-1.5 pb-2 sm:px-3 sm:pt-2 sm:pb-2">
                <div className="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                    <div className={`text-xl font-bold sm:text-2xl ${color}`}>
                        {typeof value === 'number' ? value.toLocaleString('id-ID') : value}
                    </div>
                    {change !== undefined && (
                        <div className={`flex items-center gap-1 text-xs sm:text-sm ${getTrendColor()}`}>
                            {getTrendIcon()}
                            <span className="font-medium">{Math.abs(change)}%</span>
                        </div>
                    )}
                </div>
                {subtitle && <CardDescription className="mt-0.5 text-xs sm:text-sm">{subtitle}</CardDescription>}
            </CardContent>
        </Card>
    );
}
