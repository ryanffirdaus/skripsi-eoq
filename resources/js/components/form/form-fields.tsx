import { colors } from '@/lib/colors';
import { cn } from '@/lib/utils';
import React from 'react';

interface FormFieldProps {
    label: string;
    id: string;
    error?: string;
    required?: boolean;
    children: React.ReactNode;
    className?: string;
}

export function FormField({ label, id, error, required = false, children, className }: FormFieldProps) {
    return (
        <div className={cn('space-y-2', className)}>
            <label htmlFor={id} className={colors.label.base}>
                {label}
                {required && <span className="ml-1 text-red-500 dark:text-red-400">*</span>}
            </label>
            {children}
            {error && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>}
        </div>
    );
}

interface TextInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    error?: string;
}

export function TextInput({ error, className, ...props }: TextInputProps) {
    return <input className={cn(error ? colors.input.error : colors.input.base, className)} {...props} />;
}

interface TextAreaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
    error?: string;
}

export function TextArea({ error, className, ...props }: TextAreaProps) {
    return <textarea className={cn(error ? colors.input.error : colors.input.base, 'resize-none', className)} {...props} />;
}

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
    error?: string;
    options: Array<{ value: string | number; label: string; disabled?: boolean }>;
    placeholder?: string;
}

export function Select({ error, options, placeholder, className, ...props }: SelectProps) {
    return (
        <select className={cn(error ? colors.input.error : colors.input.base, className)} {...props}>
            {placeholder && (
                <option value="" disabled>
                    {placeholder}
                </option>
            )}
            {options.map((option) => (
                <option key={option.value} value={option.value} disabled={option.disabled}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

interface NumberInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    error?: string;
}

export function NumberInput({ error, className, ...props }: NumberInputProps) {
    return <input type="number" className={cn(error ? colors.input.error : colors.input.base, className)} {...props} />;
}
