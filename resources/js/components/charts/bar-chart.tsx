import { formatCompactNumber } from '@/lib/utils';
import type React from 'react';
import { Bar, BarChart as RechartsBarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface BarChartProps {
    data: any[];
    xKey: string;
    yKey: string | string[];
    colors?: string[];
    showGrid?: boolean;
    height?: number;
    layout?: 'horizontal' | 'vertical';
}

export function BarChart({ data, xKey, yKey, colors = ['#3b82f6'], showGrid = true, height = 300, layout = 'horizontal' }: BarChartProps) {
    const yKeys = Array.isArray(yKey) ? yKey : [yKey];

    return (
        <ResponsiveContainer width="100%" height={height}>
            <RechartsBarChart data={data} layout={layout} margin={{ top: 10, right: 10, left: layout === 'vertical' ? 20 : 0, bottom: 0 }}>
                {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />}
                {layout === 'horizontal' ? (
                    <>
                        <XAxis dataKey={xKey} stroke="#6b7280" style={{ fontSize: '12px' }} />
                        <YAxis stroke="#6b7280" style={{ fontSize: '12px' }} tickFormatter={formatCompactNumber} />
                    </>
                ) : (
                    <>
                        <XAxis type="number" stroke="#6b7280" style={{ fontSize: '12px' }} tickFormatter={formatCompactNumber} />
                        <YAxis dataKey={xKey} type="category" stroke="#6b7280" style={{ fontSize: '12px' }} width={100} />
                    </>
                )}
                <Tooltip
                    contentStyle={{
                        backgroundColor: '#fff',
                        border: '1px solid #e5e7eb',
                        borderRadius: '8px',
                        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                    }}
                    formatter={(value: any) => [typeof value === 'number' ? value.toLocaleString('id-ID') : value]}
                />
                {yKeys.map((key, index) => (
                    <Bar key={key} dataKey={key} fill={colors[index % colors.length]} radius={[8, 8, 0, 0]} />
                ))}
            </RechartsBarChart>
        </ResponsiveContainer>
    );
}
