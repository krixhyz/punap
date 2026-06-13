import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useProfile, useUpdateProfile } from '../../api/profile';
import { useProvinces, useCities } from '../../api/products';
import { useAuth } from '../../hooks/useAuth';
import { Button } from '../../components/Button';
import { Input } from '../../components/Input';
import { Select } from '../../components/Select';
import { Avatar } from '../../components/Avatar';
import { Skeleton } from '../../components/Skeleton';

export default function EditProfilePage() {
    const navigate = useNavigate();
    const { user } = useAuth();
    const { data: profile, isLoading } = useProfile(user?.id ?? '');
    const update = useUpdateProfile();
    const { data: provinces } = useProvinces();

    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [provinceId, setProvinceId] = useState<number | undefined>();
    const [cityId, setCityId] = useState<number | undefined>();

    const { data: cities } = useCities(provinceId);

    useEffect(() => {
        if (profile) {
            setName(profile.name ?? '');
            setPhone(profile.phone ?? '');
            setProvinceId(profile.province?.id);
            setCityId(profile.city?.id);
        }
    }, [profile]);

    async function handleSave() {
        try {
            await update.mutateAsync({
                name: name || undefined,
                phone: phone || undefined,
                provinceId,
                cityId,
            });
            toast.success('Profile updated');
            navigate(`/profile/${user?.id}`);
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(msg ?? 'Failed to update profile');
        }
    }

    if (isLoading) {
        return (
            <div className="max-w-2xl mx-auto px-4 py-8 space-y-4">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-12 rounded-xl" />
                <Skeleton className="h-12 rounded-xl" />
            </div>
        );
    }

    return (
        <div className="max-w-2xl mx-auto px-4 py-8">
            <h1 className="text-2xl font-heading font-bold text-gray-900 mb-6">Edit Profile</h1>

            <div className="bg-white border border-gray-200 rounded-2xl p-6 space-y-5">
                {/* Avatar preview */}
                <div className="flex items-center gap-4">
                    <Avatar src={profile?.avatarUrl} name={profile?.name} size="xl" />
                    <div>
                        <p className="text-sm text-gray-500">Profile photo</p>
                        <p className="text-xs text-gray-400 mt-0.5">Avatar upload coming soon</p>
                    </div>
                </div>

                <Input
                    label="Full name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="Your name"
                />

                <Input
                    label="Phone number"
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="+977 98XXXXXXXX"
                />

                <Select
                    label="Province"
                    value={provinceId?.toString() ?? ''}
                    onChange={(e) => {
                        const v = e.target.value ? parseInt(e.target.value) : undefined;
                        setProvinceId(v);
                        setCityId(undefined);
                    }}
                >
                    <option value="">— Select province —</option>
                    {provinces?.map((p) => (
                        <option key={p.id} value={p.id}>{p.name}</option>
                    ))}
                </Select>

                <Select
                    label="City"
                    value={cityId?.toString() ?? ''}
                    onChange={(e) => setCityId(e.target.value ? parseInt(e.target.value) : undefined)}
                    disabled={!provinceId}
                >
                    <option value="">— Select city —</option>
                    {cities?.map((c) => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                </Select>

                <div className="flex gap-3 justify-end pt-2">
                    <Button variant="ghost" onClick={() => navigate(-1)}>Cancel</Button>
                    <Button loading={update.isPending} onClick={handleSave}>Save changes</Button>
                </div>
            </div>
        </div>
    );
}
