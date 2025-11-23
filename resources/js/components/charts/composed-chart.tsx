import type React from 'react';
import { Bar, CartesianGrid, ComposedChart as RechartsComposedChart, Legend, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface ComposedChartProps {
    data: any[];
    xKey: string;
    lines?: { key: string; color?: string }[];
    bars?: { key: string; color?: string }[];
    showGrid?: boolean;
    height?: number;
}

export function ComposedChart({ data, xKey, lines = [], bars = [], showGrid = true, height = 300 }: ComposedChartProps) {
    return (
        <ResponsiveContainer width="100%" height={height}>
            <RechartsComposedChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                {showGrid && <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />}
                <XAxis dataKey={xKey} stroke="#6b7280" style={{ fontSize: '12px' }} />
                <YAxis stroke="#6b7280" style={{ fontSize: '12px' }} tickFormatter={(value) => value.toLocaleString('id-ID')} />
                <Tooltip
                    contentStyle={{
                        backgroundColor: '#fff',
                        border: '1px solid #e5e7eb',
                        borderRadius: '8px',
                        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                    }}
                    formatter={(value: any) => [typeof value === 'number' ? value.toLocaleString('id-ID') : value]}
                />
                <Legend />
                {bars.map((bar) => (
                    <Bar key={bar.key} dataKey={bar.key} fill={bar.color || '#3b82f6'} radius={[8, 8, 0, 0]} />
                ))}
                {lines.map((line) => (
                    <Line key={line.key} type="monotone" dataKey={line.key} stroke={line.color || '#10b981'} strokeWidth={2} />
                ))}
            </RechartsComposedChart>
        </ResponsiveContainer>
    );
}
