import { View, Text } from 'react-native';

const STATUS_COLORS: Record<string, { bg: string; text: string; label: string }> = {
    PENDING: { bg: '#FEF3C7', text: '#92400E', label: 'Pending' },
    PENDING_PAYMENT: { bg: '#FEF3C7', text: '#92400E', label: 'Pending Payment' },
    PAID: { bg: '#D1FAE5', text: '#065F46', label: 'Paid' },
    ACTIVE: { bg: '#D1FAE5', text: '#065F46', label: 'Active' },
    COMPLETED: { bg: '#D1FAE5', text: '#065F46', label: 'Completed' },
    CANCELLED: { bg: '#FEE2E2', text: '#991B1B', label: 'Cancelled' },
    DISPUTED: { bg: '#FEE2E2', text: '#991B1B', label: 'Disputed' },
    REJECTED: { bg: '#FEE2E2', text: '#991B1B', label: 'Rejected' },
    APPROVED: { bg: '#D1FAE5', text: '#065F46', label: 'Approved' },
    COUNTERED: { bg: '#EDE9FE', text: '#4C1D95', label: 'Countered' },
    AWAITING_PAYMENT: { bg: '#FEF3C7', text: '#92400E', label: 'Awaiting Payment' },
    CONFIRMATION_PENDING: { bg: '#DBEAFE', text: '#1E40AF', label: 'Confirm Pending' },
    RETURN_REQUESTED: { bg: '#FEF3C7', text: '#92400E', label: 'Return Requested' },
    BUY: { bg: '#D1FAE5', text: '#065F46', label: 'Buy' },
    RENT: { bg: '#DBEAFE', text: '#1E40AF', label: 'Rent' },
    SWAP: { bg: '#EDE9FE', text: '#4C1D95', label: 'Swap' },
};

interface Props {
    status: string;
    size?: 'sm' | 'md';
}

export function StatusBadge({ status, size = 'sm' }: Props) {
    const config = STATUS_COLORS[status] ?? { bg: '#F3F4F6', text: '#374151', label: status };
    const padding = size === 'sm' ? { paddingHorizontal: 8, paddingVertical: 3 } : { paddingHorizontal: 12, paddingVertical: 5 };
    const fontSize = size === 'sm' ? 11 : 13;

    return (
        <View style={{ backgroundColor: config.bg, borderRadius: 999, ...padding }}>
            <Text style={{ color: config.text, fontSize, fontWeight: '500' }}>{config.label}</Text>
        </View>
    );
}
