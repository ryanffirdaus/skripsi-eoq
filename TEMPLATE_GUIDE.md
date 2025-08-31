# Template dan Color Palette Guide

## Daftar Isi

1. [Color Palette](#color-palette)
2. [Form Template](#form-template)
3. [Table Template](#table-template)
4. [Form Fields](#form-fields)
5. [Contoh Penggunaan](#contoh-penggunaan)

## Color Palette

File: `resources/js/lib/colors.ts`

### Color Variants Utama

```typescript
// Primary colors (Blue theme)
colors.primary.DEFAULT; // bg-blue-600 text-white hover:bg-blue-700
colors.primary.light; // bg-blue-50 text-blue-600 hover:bg-blue-100
colors.primary.outline; // border-blue-600 text-blue-600 hover:bg-blue-50

// Success colors (Green)
colors.success.DEFAULT; // bg-green-600 text-white hover:bg-green-700
colors.success.light; // bg-green-50 text-green-600

// Danger colors (Red)
colors.danger.DEFAULT; // bg-red-600 text-white hover:bg-red-700
colors.danger.light; // bg-red-50 text-red-600

// Form inputs
colors.input.base; // Input styling dengan dark mode support
colors.input.error; // Input dengan border merah untuk error
colors.input.success; // Input dengan border hijau

// Text colors
colors.text.primary; // text-gray-900 dark:text-white
colors.text.secondary; // text-gray-600 dark:text-gray-400

// Background colors
colors.background.primary; // bg-white dark:bg-gray-900
colors.background.secondary; // bg-gray-50 dark:bg-gray-800

// Card styling
colors.card.base; // Card dengan border dan shadow
colors.card.body; // Padding untuk body card
```

### Button Variants

```typescript
buttonVariants.primary; // Blue button
buttonVariants.secondary; // Gray button
buttonVariants.outline; // Outline button
buttonVariants.destructive; // Red button untuk delete
buttonVariants.ghost; // Transparent button
```

### Alert Variants

```typescript
alertVariants.success; // Green alert
alertVariants.error; // Red alert
alertVariants.warning; // Yellow alert
alertVariants.info; // Blue alert
```

## Form Template

File: `resources/js/components/form/form-template.tsx`

### Props

```typescript
interface FormTemplateProps {
    title: string; // Judul halaman
    breadcrumbs: BreadcrumbItem[]; // Navigation breadcrumbs
    backUrl: string; // URL untuk tombol back/cancel
    onSubmit: (e: React.FormEvent) => void;
    processing?: boolean; // Loading state
    submitText?: string; // Text tombol submit (default: "Save")
    processingText?: string; // Text saat loading (default: "Saving...")
    children: React.ReactNode; // Form fields utama
    sections?: Array<{
        // Section tambahan (optional)
        title?: string;
        children: React.ReactNode;
        className?: string;
    }>;
    className?: string; // Custom styling
}
```

### Contoh Penggunaan

```tsx
<FormTemplate
    title="Create New User"
    breadcrumbs={breadcrumbs}
    backUrl="/users"
    onSubmit={handleSubmit}
    processing={processing}
    submitText="Create User"
    processingText="Creating..."
    sections={[passwordSection]} // Section untuk password fields
>
    {/* Form fields utama di sini */}
    <FormField label="Name" id="name" required>
        <TextInput />
    </FormField>
</FormTemplate>
```

## Table Template

File: `resources/js/components/table/table-template.tsx`

### Props Utama

```typescript
interface TableTemplateProps<T> {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    data: PaginatedData<T>; // Data dengan pagination
    columns: ColumnDefinition<T>[]; // Definisi kolom
    createUrl?: string; // URL untuk create button
    createButtonText?: string; // Text create button
    searchPlaceholder?: string; // Placeholder search
    filters?: FilterObject; // Current filters
    filterOptions?: FilterOption[]; // Filter options
    baseUrl: string; // Base URL untuk filtering
    actions?: ActionButton<T>[]; // Action buttons per row
    flash?: { message?: string }; // Flash messages
    onDelete?: (item: T) => void; // Delete handler
}
```

### Column Definition

```typescript
interface ColumnDefinition<T> {
    key: string; // Key dari data
    label: string; // Header label
    sortable?: boolean; // Apakah bisa di-sort
    render?: (item: T) => React.ReactNode; // Custom render
    className?: string; // Custom styling
}
```

### Action Buttons

```typescript
// Helper functions untuk membuat action buttons
createEditAction<T>((item) => `/users/${item.id}/edit`);
createDeleteAction<T>(handleDelete);
createViewAction<T>((item) => `/users/${item.id}`);
```

## Form Fields

File: `resources/js/components/form/form-fields.tsx`

### Available Components

```tsx
// Form Field Container
<FormField
  label="Field Label"
  id="field_id"
  error={errors.field}
  required={true}
>
  <TextInput />
</FormField>

// Text Input
<TextInput
  id="name"
  value={data.name}
  onChange={(e) => setData('name', e.target.value)}
  placeholder="Enter name"
  error={errors.name}
/>

// Number Input
<NumberInput
  id="price"
  value={data.price}
  onChange={(e) => setData('price', Number(e.target.value))}
  placeholder="Enter price"
  error={errors.price}
/>

// Select Dropdown
<Select
  id="role_id"
  value={data.role_id}
  onChange={(e) => setData('role_id', e.target.value)}
  options={[
    { value: "1", label: "Admin" },
    { value: "2", label: "User" }
  ]}
  placeholder="Select role"
  error={errors.role_id}
/>

// Textarea
<TextArea
  id="description"
  value={data.description}
  onChange={(e) => setData('description', e.target.value)}
  placeholder="Enter description"
  rows={4}
  error={errors.description}
/>
```

## Contoh Penggunaan

### 1. Create Form (Users/Create.tsx)

```tsx
import FormTemplate from '@/components/form/form-template';
import { FormField, TextInput, Select } from '@/components/form/form-fields';

export default function Create({ roles }) {
    const { data, setData, post, processing, errors } = useForm({
        nama_lengkap: '',
        email: '',
        role_id: '',
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e) {
        e.preventDefault();
        post('/users');
    }

    const roleOptions = roles.map((role) => ({
        value: role.role_id.toString(),
        label: role.name,
    }));

    const passwordSection = {
        title: 'Password',
        children: (
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="password" label="Password" required>
                    <TextInput type="password" />
                </FormField>
                <FormField id="password_confirmation" label="Confirm Password" required>
                    <TextInput type="password" />
                </FormField>
            </div>
        ),
    };

    return (
        <FormTemplate
            title="Create New User"
            breadcrumbs={breadcrumbs}
            backUrl="/users"
            onSubmit={handleSubmit}
            processing={processing}
            sections={[passwordSection]}
        >
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <FormField id="nama_lengkap" label="Full Name" required>
                    <TextInput />
                </FormField>
                <FormField id="email" label="Email" required>
                    <TextInput type="email" />
                </FormField>
                <FormField id="role_id" label="Role" required>
                    <Select options={roleOptions} placeholder="Select role" />
                </FormField>
            </div>
        </FormTemplate>
    );
}
```

### 2. Index Table (Users/Index.tsx)

```tsx
import TableTemplate from '@/components/table/table-template';
import { createEditAction, createDeleteAction } from '@/components/table/table-actions';

export default function Index({ users, roles, filters, flash }) {
    const handleDelete = (user) => {
        router.delete(`/users/${user.user_id}`);
    };

    const columns = [
        { key: 'nama_lengkap', label: 'Name', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        {
            key: 'role',
            label: 'Role',
            sortable: true,
            render: (user) => user.role?.name || '-',
        },
    ];

    const filterOptions = [
        {
            key: 'role_id',
            label: 'Role',
            type: 'select',
            options: roles.map((role) => ({ value: role.role_id, label: role.name })),
        },
    ];

    const actions = [createEditAction((user) => `/users/${user.user_id}/edit`), createDeleteAction(handleDelete)];

    return (
        <TableTemplate
            title="Users Management"
            data={users}
            columns={columns}
            createUrl="/users/create"
            searchPlaceholder="Search by name or email..."
            filterOptions={filterOptions}
            baseUrl="/users"
            actions={actions}
            onDelete={handleDelete}
        />
    );
}
```

### 3. Edit Form (Users/Edit.tsx)

```tsx
// Sama seperti Create form, tapi dengan data yang sudah diisi
const { data, setData, put, processing, errors } = useForm({
    nama_lengkap: user.nama_lengkap || '',
    email: user.email || '',
    role_id: user.role_id || '',
    password: '',
    password_confirmation: '',
});

function handleSubmit(e) {
    e.preventDefault();
    put(`/users/${user.user_id}`);
}

// Sections untuk password dengan note "leave blank to keep current"
const passwordSection = {
    title: 'Change Password',
    children: (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <FormField id="password" label="New Password">
                <TextInput type="password" />
                <span className="text-xs text-gray-500">(leave blank to keep current)</span>
            </FormField>
            {/* ... */}
        </div>
    ),
};
```

## Tips Penggunaan

1. **Konsistensi Warna**: Gunakan `colors` object untuk semua styling agar konsisten
2. **TypeScript**: Pastikan interface extend `Record<string, unknown>` untuk table template
3. **Responsiveness**: Template sudah responsive, gunakan grid classes untuk layout
4. **Dark Mode**: Semua colors sudah support dark mode secara otomatis
5. **Error Handling**: FormField akan otomatis menampilkan error jika ada
6. **Loading States**: Template akan handle loading states otomatis dengan `processing` prop

## Customization

Untuk customization lebih lanjut:

- Edit `colors.ts` untuk mengubah color palette
- Extend FormTemplate atau TableTemplate untuk fitur tambahan
- Buat form fields custom dengan mengikuti pattern yang ada
- Tambahkan action buttons custom di table-actions.tsx
