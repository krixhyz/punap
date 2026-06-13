import { BadRequestException, Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';

@Injectable()
export class RentalDepositService {
    constructor(private readonly prisma: PrismaService) {}

    async hold(rentalBookingId: string, amount: number): Promise<void> {
        await this.prisma.rentalDeposit.create({
            data: {
                rentalBookingId,
                amount,
                status: 'HELD',
            },
        });
    }

    // Fully wired in Session 07 after gateway refund API is available
    async initiateRefund(rentalDepositId: string): Promise<void> {
        const deposit = await this.prisma.rentalDeposit.findUnique({
            where: { id: rentalDepositId },
        });
        if (!deposit) throw new BadRequestException('Deposit not found');
        if (deposit.status !== 'HELD') {
            throw new BadRequestException(`Deposit is already ${deposit.status}`);
        }
        // Gateway refund call wired in Session 07
        await this.markRefunded(rentalDepositId, 'stub-reference');
    }

    async markRefunded(rentalDepositId: string, refundReference: string): Promise<void> {
        await this.prisma.rentalDeposit.update({
            where: { id: rentalDepositId },
            data: {
                status: 'REFUNDED',
                refundedAt: new Date(),
                refundReference,
            },
        });
    }

    async forfeit(rentalDepositId: string, reason: string): Promise<void> {
        await this.prisma.rentalDeposit.update({
            where: { id: rentalDepositId },
            data: {
                status: 'FORFEITED',
                forfeitedAt: new Date(),
                forfeitReason: reason,
            },
        });
    }
}
