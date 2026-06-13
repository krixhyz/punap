import { useState } from 'react';
import toast from 'react-hot-toast';
import { ArrowLeftRight } from 'lucide-react';
import { Modal } from './Modal';
import { Button } from './Button';
import { Select } from './Select';
import { Textarea } from './Textarea';
import { Input } from './Input';
import { useMyProducts } from '../api/products';
import { useCreateSwapRequest, type MoneyDirection } from '../api/swaps';

interface Props {
    targetProductId: string;
    targetProductTitle: string;
    onClose: () => void;
}

export function SwapRequestModal({ targetProductId, targetProductTitle, onClose }: Props) {
    const { data: myProducts } = useMyProducts();
    const createSwap = useCreateSwapRequest();

    const [offeredProductId, setOfferedProductId] = useState('');
    const [message, setMessage] = useState('');
    const [moneyDirection, setMoneyDirection] = useState<MoneyDirection>('NONE');
    const [amount, setAmount] = useState('');

    const availableProducts = (myProducts ?? []).filter((p) => p.id !== targetProductId);

    async function handleSubmit() {
        if (!offeredProductId) {
            toast.error('Please select a product to offer');
            return;
        }
        try {
            const payload: {
                productId: string;
                offeredProductId: string;
                message?: string;
                offeredAmount?: number;
                askedAmount?: number;
                moneyDirection: MoneyDirection;
            } = {
                productId: targetProductId,
                offeredProductId,
                message: message || undefined,
                moneyDirection,
            };
            if (moneyDirection === 'REQUESTER_OFFERS_CASH' && amount) {
                payload.offeredAmount = parseFloat(amount);
            } else if (moneyDirection === 'OWNER_ASKS_CASH' && amount) {
                payload.askedAmount = parseFloat(amount);
            }
            await createSwap.mutateAsync(payload);
            toast.success('Swap request sent!');
            onClose();
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(msg ?? 'Failed to send swap request');
        }
    }

    return (
        <Modal open title="Propose a Swap" onClose={onClose}>
            <div className="space-y-4">
                <div className="bg-purple-50 rounded-lg p-3 flex items-center gap-2 text-sm text-purple-800">
                    <ArrowLeftRight className="w-4 h-4 shrink-0" />
                    <span>You are proposing a swap for <strong>{targetProductTitle}</strong></span>
                </div>

                <Select
                    label="Your offered product"
                    value={offeredProductId}
                    onChange={(e) => setOfferedProductId(e.target.value)}
                    disabled={availableProducts.length === 0}
                >
                    <option value="">— Select a product —</option>
                    {availableProducts.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.title}
                        </option>
                    ))}
                </Select>

                {availableProducts.length === 0 && (
                    <p className="text-sm text-amber-600">
                        You have no listed products to offer. List a product first.
                    </p>
                )}

                <Select
                    label="Cash adjustment"
                    value={moneyDirection}
                    onChange={(e) => setMoneyDirection(e.target.value as MoneyDirection)}
                >
                    <option value="NONE">No cash top-up</option>
                    <option value="REQUESTER_OFFERS_CASH">I'll add cash top-up</option>
                    <option value="OWNER_ASKS_CASH">I'm requesting cash from the owner</option>
                </Select>

                {moneyDirection !== 'NONE' && (
                    <Input
                        label={moneyDirection === 'REQUESTER_OFFERS_CASH' ? 'Amount you offer (Rs)' : 'Amount you request (Rs)'}
                        type="number"
                        min="0"
                        step="1"
                        value={amount}
                        onChange={(e) => setAmount(e.target.value)}
                        placeholder="0"
                    />
                )}

                <Textarea
                    label="Message (optional)"
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                    placeholder="Tell the owner why this is a great swap..."
                    rows={3}
                />

                <div className="flex gap-3 justify-end pt-2">
                    <Button variant="ghost" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        loading={createSwap.isPending}
                        disabled={!offeredProductId}
                        className="!bg-purple-600 hover:!bg-purple-700"
                    >
                        Send swap request
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
