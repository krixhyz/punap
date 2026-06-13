import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { WalletLedgerService } from '../wallet/wallet-ledger.service';
import { EcoScoreService } from '../eco-score/eco-score.service';

@Injectable()
export class SwapOrderService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly walletLedger: WalletLedgerService,
        private readonly ecoScore: EcoScoreService,
    ) {}

    async complete(swapRequestId: string): Promise<void> {
        let swapSnapshot: {
            ownerId: string;
            requesterId: string;
            productCondition: string;
            offeredProductCondition: string;
        } | null = null;

        await this.prisma.$transaction(async (tx) => {
            const swap = await tx.swapRequest.findUnique({
                where: { id: swapRequestId },
                include: {
                    orderConfirmation: true,
                    product: { select: { condition: true } },
                    offeredProduct: { select: { condition: true } },
                },
            });
            if (!swap) return;

            swapSnapshot = {
                ownerId: swap.ownerId,
                requesterId: swap.requesterId,
                productCondition: swap.product?.condition ?? 'GOOD',
                offeredProductCondition: swap.offeredProduct?.condition ?? 'GOOD',
            };

            await tx.swap.upsert({
                where: { swapRequestId },
                create: { swapRequestId, status: 'COMPLETED' },
                update: { status: 'COMPLETED' },
            });

            await tx.swapRequest.update({
                where: { id: swapRequestId },
                data: { status: 'COMPLETED' },
            });

            if (swap.orderConfirmation) {
                await tx.swapOrderConfirmation.update({
                    where: { swapRequestId },
                    data: { finalCompletedAt: new Date() },
                });
            }

            await tx.product.updateMany({
                where: { id: { in: [swap.productId, swap.offeredProductId] } },
                data: { status: 'SWAPPED' },
            });
        });

        if (swapSnapshot !== null) {
            // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
            const { ownerId, requesterId, productCondition, offeredProductCondition } = swapSnapshot!;
            await Promise.all([
                this.walletLedger.releaseSwapFunds(swapRequestId),
                this.ecoScore.recordEcoImpact({
                    userId: ownerId,
                    condition: productCondition,
                    transactionType: 'SWAP',
                    transactionId: `${swapRequestId}:owner`,
                }),
                this.ecoScore.recordEcoImpact({
                    userId: requesterId,
                    condition: offeredProductCondition,
                    transactionType: 'SWAP',
                    transactionId: `${swapRequestId}:requester`,
                }),
            ]);
        }
    }

    async onPaymentComplete(swapRequestId: string): Promise<void> {
        const swap = await this.prisma.swapRequest.findUnique({ where: { id: swapRequestId } });
        if (!swap) return;
        if (swap.status !== 'AWAITING_PAYMENT') return;

        await this.prisma.$transaction(async (tx) => {
            await tx.swapRequest.update({
                where: { id: swapRequestId },
                data: { status: 'CONFIRMATION_PENDING' },
            });
            await tx.swapOrderConfirmation.upsert({
                where: { swapRequestId },
                create: { swapRequestId },
                update: {},
            });
            await tx.swapNegotiationEvent.create({
                data: {
                    swapRequestId,
                    actorId: swap.requesterId,
                    type: 'ACCEPT',
                    message: 'Payment completed — awaiting physical confirmation',
                },
            });
        });
    }
}
