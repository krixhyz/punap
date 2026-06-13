import { useState } from 'react';
import toast from 'react-hot-toast';
import { useAdminSettings, useUpdateSetting } from '../../api/admin';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Skeleton } from '../../components/Skeleton';

function SettingRow({ settingKey, value, description }: { settingKey: string; value: string; description?: string | null }) {
    const [editing, setEditing] = useState(false);
    const [newValue, setNewValue] = useState(value);
    const update = useUpdateSetting();

    async function handleSave() {
        try {
            await update.mutateAsync({ key: settingKey, value: newValue });
            toast.success(`Updated ${settingKey}`);
            setEditing(false);
        } catch { toast.error('Failed to update setting'); }
    }

    return (
        <tr className="hover:bg-gray-50">
            <td className="px-4 py-3">
                <p className="font-mono text-sm text-gray-800">{settingKey}</p>
                {description && <p className="text-xs text-gray-400 mt-0.5">{description}</p>}
            </td>
            <td className="px-4 py-3">
                {editing ? (
                    <div className="flex items-center gap-2">
                        <Input
                            value={newValue}
                            onChange={(e) => setNewValue(e.target.value)}
                            className="max-w-36"
                        />
                        <Button size="sm" loading={update.isPending} onClick={handleSave}>Save</Button>
                        <Button size="sm" variant="ghost" onClick={() => { setEditing(false); setNewValue(value); }}>Cancel</Button>
                    </div>
                ) : (
                    <div className="flex items-center gap-2">
                        <span className="font-mono text-sm text-gray-700">{value}</span>
                        <button
                            onClick={() => setEditing(true)}
                            className="text-xs text-[#1a6b3c] hover:underline ml-2"
                        >
                            Edit
                        </button>
                    </div>
                )}
            </td>
        </tr>
    );
}

export default function AdminSettingsPage() {
    const { data: settings, isLoading } = useAdminSettings();

    return (
        <div className="p-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Platform Settings</h1>

            {isLoading ? (
                <div className="space-y-2">{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-12 rounded-lg" />)}</div>
            ) : (
                <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Key</th>
                                <th className="text-left px-4 py-3 text-xs text-gray-500 uppercase">Value</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {settings?.map((s) => (
                                <SettingRow
                                    key={s.key}
                                    settingKey={s.key}
                                    value={s.value}
                                    description={s.description}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
