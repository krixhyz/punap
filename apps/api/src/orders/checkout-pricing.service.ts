import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';

export interface PricingBreakdown {
    subtotal: number;
    serviceFee: number;
    totalAmount: number;
    sellerAmount: number;
    platformAmount: number;
    feePercentage: number;
}

function round2(n: number): number {
    return Math.round(n * 100) / 100;
}

@Injectable()
export class CheckoutPricingService {
    constructor(private readonly prisma: PrismaService) {}

    async calculatePurchase(subtotal: number): Promise<PricingBreakdown> {
        const feePercent = await this.getCommissionPercent();
        const sub = round2(subtotal);
        const serviceFee = round2(sub * (feePercent / 100));
        return {
            subtotal: sub,
            serviceFee,
            totalAmount: round2(sub + serviceFee),
            sellerAmount: sub,
            platformAmount: serviceFee,
            feePercentage: feePercent,
        };
    }

    async calculateRent(rentFee: number, deposit: number): Promise<PricingBreakdown & { deposit: number }> {
        const feePercent = await this.getCommissionPercent();
        const fee = round2(rentFee);
        const dep = round2(deposit);
        const serviceFee = round2(fee * (feePercent / 100));
        return {
            subtotal: fee,
            serviceFee,
            deposit: dep,
            totalAmount: round2(fee + dep + serviceFee),
            sellerAmount: fee,
            platformAmount: serviceFee,
            feePercentage: feePercent,
        };
    }

    private async getCommissionPercent(): Promise<number> {
        const setting = await this.prisma.platformSetting.findUnique({
            where: { key: 'commission_percent' },
        });
        return setting ? parseFloat(setting.value) : 3.0;
    }
}
