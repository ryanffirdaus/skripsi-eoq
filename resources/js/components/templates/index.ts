// Form Components
export { default as FormTemplate } from '../form/form-template';
export { FormField, TextInput, TextArea, Select, NumberInput } from '../form/form-fields';

// Table Components
export { default as TableTemplate } from '../table/table-template';
export { createEditAction, createDeleteAction, createViewAction } from '../table/table-actions';
export type { ActionButton } from '../table/table-actions';

// Colors and Utilities
export { colors, buttonVariants, alertVariants, getColorClass } from '@/lib/colors';
