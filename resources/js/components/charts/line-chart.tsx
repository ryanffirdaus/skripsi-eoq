import { formatCompactNumber } from '@/lib/utils';
import type React from 'react';
import { CartesianGrid, Legend, Line, LineChart as RechartsLineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface LineChartProps {
    data: any[];
    xKey: string;
    yKey: string | string[];
    colors?: string[];
    showGrid?: boolean;
    height?: number;
}

export function LineChart({ data, xKey, yKey, colors = ['#3b82f6'], showGrid = true, height = 300 }: LineChartProps) {
    const yKeys = Array.isArray(yKey) ? yKey : [yKey];

    return (
        <ResponsiveContainer width="100%" height={height}>
            <RechartsLineChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />}
                <XAxis dataKey={xKey} stroke="#6b7280" style={{ fontSize: '12px' }} />
                <YAxis stroke="#6b7280" style={{ fontSize: '12px' }} tickFormatter={formatCompactNumber} />
                <Tooltip
                    contentStyle={{
                        backgroundColor: '#fff',
                        border: '1px solid #e5e7eb',
                        borderRadius: '8px',
                        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                    }}
                    formatter={(value: any) => [typeof value === 'number' ? value.toLocaleString('id-ID') : value]}
                />
                <Legend wrapperStyle={{ fontSize: '12px' }} />
                {yKeys.map((key, index) => (
                    <Line key={key} type="monotone" dataKey={key} stroke={colors[index % colors.length]} strokeWidth={2} dot={{ fill: colors[index % colors.length], r: 4 }} activeDot={{ r: 6 }} />
                ))}
            </RechartsLineChart>
        </ResponsiveContainer>
    );
}
