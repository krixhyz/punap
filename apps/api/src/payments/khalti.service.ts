import { Injectable, Logger } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import axios from 'axios';

export interface KhaltiInitiatePayload {
    return_url: string;
    website_url: string;
    amount: number; // in paisa
    purchase_order_id: string;
    purchase_order_name: string;
    customer_info?: { name?: string; email?: string; phone?: string };
}

export interface KhaltiInitiateResult {
    pidx: string;
    paymentUrl: string;
}

@Injectable()
export class KhaltiService {
    private readonly logger = new Logger(KhaltiService.name);

    constructor(private readonly config: ConfigService) {}

    toPaisa(amount: number): number {
        return Math.round(amount * 100);
    }

    async initiatePayment(payload: KhaltiInitiatePayload): Promise<KhaltiInitiateResult> {
        const response = await axios.post(
            this.config.get<string>('KHALTI_INITIATE_URL', 'https://a.khalti.com/api/v2/epayment/initiate/'),
            payload,
            { headers: this.authHeaders() },
        );
        return {
            pidx: response.data.pidx,
            paymentUrl: response.data.payment_url,
        };
    }

    async lookupPayment(pidx: string): Promise<{ status: string; totalAmount: number }> {
        const response = await axios.post(
            this.config.get<string>('KHALTI_LOOKUP_URL', 'https://a.khalti.com/api/v2/epayment/lookup/'),
            { pidx },
            { headers: this.authHeaders() },
        );
        return {
            status: response.data.status,
            totalAmount: response.data.total_amount,
        };
    }

    async refundPayment(payload: Record<string, unknown>): Promise<void> {
        const refundUrl = this.config.get<string>('KHALTI_REFUND_URL');
        if (!refundUrl) {
            this.logger.warn('Khalti refund URL not configured — skipping refund');
            return;
        }
        await axios.post(refundUrl, payload, { headers: this.authHeaders() });
    }

    private authHeaders() {
        return {
            Authorization: `Key ${this.config.get<string>('KHALTI_SECRET_KEY', '')}`,
            'Content-Type': 'application/json',
        };
    }
}
