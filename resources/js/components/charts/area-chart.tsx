import type React from 'react';
import { Area, AreaChart as RechartsAreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface AreaChartProps {
    data: any[];
    xKey: string;
    yKey: string;
    color?: string;
    gradient?: boolean;
    showGrid?: boolean;
    height?: number;
}

export function AreaChart({ data, xKey, yKey, color = '#3b82f6', gradient = true, showGrid = true, height = 300 }: AreaChartProps) {
    return (
        <ResponsiveContainer width="100%" height={height}>
            <RechartsAreaChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
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
                    formatter={(value: any) => [typeof value === 'number' ? value.toLocaleString('id-ID') : value, yKey]}
                />
                {gradient ? (
                    <>
                        <defs>
                            <linearGradient id={`color${yKey}`} x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor={color} stopOpacity={0.8} />
                                <stop offset="95%" stopColor={color} stopOpacity={0.1} />
                            </linearGradient>
                        </defs>
                        <Area type="monotone" dataKey={yKey} stroke={color} fill={`url(#color${yKey})`} strokeWidth={2} />
                    </>
                ) : (
                    <Area type="monotone" dataKey={yKey} stroke={color} fill={color} fillOpacity={0.2} strokeWidth={2} />
                )}
            </RechartsAreaChart>
        </ResponsiveContainer>
    );
}
