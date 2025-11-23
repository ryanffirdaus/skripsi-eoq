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
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">{title}</CardTitle>
                    {icon && <div className={`rounded-lg p-2 ${color.replace('text-', 'bg-').replace('-600', '-100')} dark:${color.replace('text-', 'bg-').replace('-600', '-900')}`}>{icon}</div>}
                </div>
            </CardHeader>
            <CardContent>
                <div className="flex items-baseline justify-between">
                    <div className={`text-3xl font-bold ${color}`}>{typeof value === 'number' ? value.toLocaleString('id-ID') : value}</div>
                    {change !== undefined && (
                        <div className={`flex items-center gap-1 text-sm ${getTrendColor()}`}>
                            {getTrendIcon()}
                            <span className="font-medium">{Math.abs(change)}%</span>
                        </div>
                    )}
                </div>
                {subtitle && <CardDescription className="mt-1">{subtitle}</CardDescription>}
            </CardContent>
        </Card>
    );
}
