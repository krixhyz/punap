import { Injectable, Logger } from '@nestjs/common';
import { Cron, CronExpression } from '@nestjs/schedule';
import { PrismaService } from '../prisma/prisma.service';

const RESERVATION_TTL_MINUTES = 15;

@Injectable()
export class InventoryReservationService {
    private readonly logger = new Logger(InventoryReservationService.name);

    constructor(private readonly prisma: PrismaService) {}

    /**
     * Decrements product quantity by `quantity` and sets `reservedUntil` on the order.
     * Call inside the same $transaction that creates the Order row.
     */
    async reserve(productId: string, quantity: number, ttlMinutes = RESERVATION_TTL_MINUTES): Promise<Date> {
        const reservedUntil = new Date(Date.now() + ttlMinutes * 60 * 1000);

        await this.prisma.$executeRaw`
            UPDATE "Product"
            SET quantity = quantity - ${quantity}
            WHERE id = ${productId}              AND quantity >= ${quantity}
        `;

        return reservedUntil;
    }

    /**
     * Restores product quantity by the amount that was reserved in the given order.
     * Safe to call even if the order was already released.
     */
    async release(orderId: string): Promise<void> {
        const order = await this.prisma.order.findUnique({
            where: { id: orderId },
            select: { productId: true, quantity: true, status: true },
        });
        if (!order) return;

        await this.prisma.$executeRaw`
            UPDATE "Product"
            SET quantity = quantity + ${order.quantity}
            WHERE id = ${order.productId}        `;
    }

    /**
     * Cron: every 5 minutes, cancel PENDING orders whose reservation has expired
     * and restore their inventory.
     */
    @Cron(CronExpression.EVERY_5_MINUTES)
    async releaseExpiredReservations(): Promise<void> {
        const expired = await this.prisma.order.findMany({
            where: {
                status: 'PENDING',
                reservedUntil: { lt: new Date() },
            },
            select: { id: true, productId: true, quantity: true },
        });

        if (expired.length === 0) return;

        this.logger.log(`Releasing ${expired.length} expired order reservation(s)`);

        for (const order of expired) {
            await this.prisma.$transaction([
                this.prisma.$executeRaw`
                    UPDATE "Product" SET quantity = quantity + ${order.quantity}
                    WHERE id = ${order.productId}                `,
                this.prisma.order.update({
                    where: { id: order.id },
                    data: { status: 'CANCELLED' },
                }),
            ]);
        }
    }
}
