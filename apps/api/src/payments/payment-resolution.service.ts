import { Injectable, Logger } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { OrdersService } from '../orders/orders.service';
import { RentalsService } from '../rentals/rentals.service';
import { SwapOrderService } from '../swaps/swap-order.service';

@Injectable()
export class PaymentResolutionService {
    private readonly logger = new Logger(PaymentResolutionService.name);

    constructor(
        private readonly prisma: PrismaService,
        private readonly ordersService: OrdersService,
        private readonly rentalsService: RentalsService,
        private readonly swapOrderService: SwapOrderService,
    ) {}

    async resolve(paymentId: string): Promise<void> {
        const payment = await this.prisma.payment.findUnique({ where: { id: paymentId } });
        if (!payment) return;

        // Idempotency — do not re-process already-completed payments
        if (payment.status === 'COMPLETE') {
            this.logger.log(`Payment ${paymentId} already resolved, skipping`);
            return;
        }

        await this.prisma.payment.update({
            where: { id: paymentId },
            data: { status: 'COMPLETE' },
        });

        switch (payment.sourceType) {
            case 'ORDER':
                await this.ordersService.onPaymentComplete(payment.sourceId);
                break;
            case 'RENTAL':
                await this.rentalsService.onPaymentComplete(payment.sourceId);
                break;
            case 'SWAP':
                await this.swapOrderService.onPaymentComplete(payment.sourceId);
                break;
        }
    }
}
