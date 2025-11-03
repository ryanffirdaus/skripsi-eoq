import { InfoSection } from '@/components/info-section';
import ShowPageTemplate from '@/components/templates/show-page-template';
import TimestampSection from '@/components/timestamp-section';
import { type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';

interface Role {
    role_id: string;
    name: string;
}

interface UserRef {
    user_id: string;
    nama_lengkap: string;
}

interface User {
    user_id: string;
    nama_lengkap: string;
    email: string;
    role_id: string;
    nama_role: string;
    created_at: string;
    updated_at: string;
    dibuat_oleh_id?: string;
    diupdate_oleh_id?: string;
    dibuat_oleh?: UserRef | null; // This is the relationship
    diupdate_oleh?: UserRef | null; // This is the relationship
}

interface Props {
    user: User;
}

export default function Show({ user }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Users',
            href: '/users',
        },
        {
            title: `View ${user.nama_lengkap}`,
            href: `/users/${user.user_id}`,
        },
    ];

    const actions = [
        {
            label: 'Edit User',
            href: `/users/${user.user_id}/edit`,
            variant: 'outline' as const,
        },
        {
            label: 'Kembali',
            href: '/users',
            variant: 'outline' as const,
        },
    ];

    const badge = user.role
        ? {
              label: user.role.name,
              color: 'bg-blue-100 text-blue-800 border-blue-200',
          }
        : undefined;

    const personalInfo = [
        {
            label: 'Nama Lengkap',
            value: <span className="text-lg font-medium">{user.nama_lengkap}</span>,
        },
        {
            label: 'Email',
            value: (
                <Link href={`mailto:${user.email}`} className="text-blue-600 hover:underline dark:text-blue-400">
                    {user.email}
                </Link>
            ),
        },
        {
            label: 'User ID',
            value: <span className="rounded bg-gray-100 px-2 py-1 font-mono text-sm dark:bg-gray-800">{user.user_id}</span>,
        },
    ];

    const systemInfo = [
        {
            label: 'Role',
            value: user.role ? (
                <span className="inline-flex items-center rounded-full border border-blue-200 bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                    {user.role.name}
                </span>
            ) : (
                <span className="text-gray-500 dark:text-gray-400">No role assigned</span>
            ),
        },
        {
            label: 'Role ID',
            value: <span className="font-mono text-sm">{user.role_id || '-'}</span>,
        },
    ];

    return (
        <ShowPageTemplate
            title={user.nama_lengkap}
            pageTitle={`View User ${user.nama_lengkap}`}
            breadcrumbs={breadcrumbs}
            subtitle={`User ID: ${user.user_id}`}
            badge={badge}
            actions={actions}
        >
            <div className="space-y-8">
                <InfoSection title="Informasi Personal" items={personalInfo} />

                <InfoSection title="Informasi Sistem" items={systemInfo} />

                <TimestampSection
                    createdAt={user.created_at}
                    updatedAt={user.updated_at}
                    createdBy={user.dibuat_oleh?.nama_lengkap}
                    updatedBy={user.diupdate_oleh?.nama_lengkap}
                />
            </div>
        </ShowPageTemplate>
    );
}
