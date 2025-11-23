import type React from 'react';
import { Cell, Legend, Pie, PieChart as RechartsPieChart, ResponsiveContainer, Tooltip } from 'recharts';

interface PieChartProps {
    data: any[];
    nameKey: string;
    valueKey: string;
    colors?: string[];
    height?: number;
    showLegend?: boolean;
}

const DEFAULT_COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

export function PieChart({ data, nameKey, valueKey, colors = DEFAULT_COLORS, height = 300, showLegend = true }: PieChartProps) {
    return (
        <ResponsiveContainer width="100%" height={height}>
            <RechartsPieChart margin={{ top: 20, right: 0, bottom: 0, left: 0 }}>
                <Pie data={data} dataKey={valueKey} nameKey={nameKey} cx="50%" cy="50%" outerRadius={80} label labelLine={false}>
                    {data.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={colors[index % colors.length]} />
                    ))}
                </Pie>
                <Tooltip
                    contentStyle={{
                        backgroundColor: '#fff',
                        border: '1px solid #e5e7eb',
                        borderRadius: '8px',
                        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                    }}
                    formatter={(value: any) => [typeof value === 'number' ? value.toLocaleString('id-ID') : value]}
                />
                {showLegend && <Legend verticalAlign="bottom" height={36} />}
            </RechartsPieChart>
        </ResponsiveContainer>
    );
}
