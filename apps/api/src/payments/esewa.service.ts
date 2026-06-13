import { BadRequestException, Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { createHmac } from 'crypto';

export interface EsewaFormFields {
    amount: string;
    tax_amount: string;
    total_amount: string;
    transaction_uuid: string;
    product_code: string;
    product_service_charge: string;
    product_delivery_charge: string;
    success_url: string;
    failure_url: string;
    signed_field_names: string;
    signature: string;
}

@Injectable()
export class EsewaService {
    constructor(private readonly config: ConfigService) {}

    getFormFields(params: {
        amount: number;
        taxAmount: number;
        totalAmount: number;
        transactionUuid: string;
        successUrl: string;
        failureUrl: string;
    }): EsewaFormFields {
        const productCode = this.config.get<string>('ESEWA_MERCHANT_CODE', 'EPAYTEST');
        const secretKey = this.config.get<string>('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');

        const signature = this.buildSignature(
            params.totalAmount.toFixed(2),
            params.transactionUuid,
            productCode,
            secretKey,
        );

        return {
            amount: params.amount.toFixed(2),
            tax_amount: params.taxAmount.toFixed(2),
            total_amount: params.totalAmount.toFixed(2),
            transaction_uuid: params.transactionUuid,
            product_code: productCode,
            product_service_charge: '0',
            product_delivery_charge: '0',
            success_url: params.successUrl,
            failure_url: params.failureUrl,
            signed_field_names: 'total_amount,transaction_uuid,product_code',
            signature,
        };
    }

    verifyCallback(payload: Record<string, string>): boolean {
        const secretKey = this.config.get<string>('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
        const signedFieldNames = payload['signed_field_names'] ?? '';
        if (!signedFieldNames) return false;

        const fields = signedFieldNames.split(',');
        const pairs: string[] = [];
        for (const field of fields) {
            const f = field.trim();
            if (!f || !(f in payload)) return false;
            pairs.push(`${f}=${payload[f]}`);
        }

        const message = pairs.join(',');
        const expected = createHmac('sha256', secretKey).update(message).digest('base64');
        // Constant-time comparison
        const received = payload['signature'] ?? '';
        if (expected.length !== received.length) return false;
        let diff = 0;
        for (let i = 0; i < expected.length; i++) {
            diff |= expected.charCodeAt(i) ^ received.charCodeAt(i);
        }
        return diff === 0;
    }

    getPaymentUrl(): string {
        return this.config.get<string>(
            'ESEWA_PAYMENT_URL',
            'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
        );
    }

    private buildSignature(
        totalAmount: string,
        transactionUuid: string,
        productCode: string,
        secretKey: string,
    ): string {
        const message = `total_amount=${totalAmount},transaction_uuid=${transactionUuid},product_code=${productCode}`;
        return createHmac('sha256', secretKey).update(message).digest('base64');
    }
}
