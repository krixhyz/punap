import { useState } from 'react';
import { AlertTriangle } from 'lucide-react';
import toast from 'react-hot-toast';
import { Modal } from './Modal';
import { Button } from './Button';
import { Input } from './Input';
import { Textarea } from './Textarea';
import { useOpenDispute } from '../api/disputes';

interface Props {
    respondentId: string;
    respondentName: string;
    transactionType: 'ORDER' | 'RENTAL' | 'SWAP';
    orderId?: string;
    rentalBookingId?: string;
    swapId?: string;
    onClose: () => void;
}

export function OpenDisputeModal({
    respondentId,
    respondentName,
    transactionType,
    orderId,
    rentalBookingId,
    swapId,
    onClose,
}: Props) {
    const [subject, setSubject] = useState('');
    const [description, setDescription] = useState('');
    const openDispute = useOpenDispute();

    async function handleSubmit() {
        if (!subject.trim() || !description.trim()) {
            toast.error('Please fill in all required fields');
            return;
        }
        try {
            await openDispute.mutateAsync({
                respondentId,
                transactionType,
                orderId,
                rentalBookingId,
                swapId,
                subject: subject.trim(),
                description: description.trim(),
            });
            toast.success('Dispute opened. Our team will review it.');
            onClose();
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(msg ?? 'Failed to open dispute');
        }
    }

    return (
        <Modal open title="Open a Dispute" onClose={onClose}>
            <div className="space-y-4">
                <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                    <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                    <p className="text-sm text-amber-800">
                        Opening a dispute against <strong>{respondentName}</strong> for a {transactionType.toLowerCase()} transaction. Our admin team will review within 48 hours.
                    </p>
                </div>

                <Input
                    label="Subject *"
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    placeholder="Brief description of the issue"
                />

                <Textarea
                    label="Description *"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    placeholder="Describe the issue in detail. Include what happened, when, and what resolution you're seeking."
                    rows={4}
                />

                <div className="flex gap-3 justify-end pt-2">
                    <Button variant="ghost" onClick={onClose}>Cancel</Button>
                    <Button
                        variant="danger"
                        loading={openDispute.isPending}
                        onClick={handleSubmit}
                        disabled={!subject || !description}
                    >
                        Open dispute
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
