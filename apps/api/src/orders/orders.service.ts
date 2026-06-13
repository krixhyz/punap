import {
    BadRequestException,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { CheckoutPricingService } from './checkout-pricing.service';
import { InventoryReservationService } from './inventory-reservation.service';
import { WalletLedgerService } from '../wallet/wallet-ledger.service';
import { EcoScoreService } from '../eco-score/eco-score.service';
import { NotificationsService } from '../notifications/notifications.service';
import { CreateOrderDto } from './dto/create-order.dto';
import { OrdersQueryDto } from './dto/orders-query.dto';

const PRODUCT_SNAPSHOT = {
    id: true,
    title: true,
    images: true,
    price: true,
    sellerId: true,
    seller: { select: { id: true, name: true, avatarUrl: true } },
};

@Injectable()
export class OrdersService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly pricing: CheckoutPricingService,
        private readonly inventory: InventoryReservationService,
        private readonly walletLedger: WalletLedgerService,
        private readonly ecoScore: EcoScoreService,
        private readonly notifications: NotificationsService,
    ) {}

    // ── Create ─────────────────────────────────────────────────────────────────
    async create(buyerId: string, dto: CreateOrderDto) {
        const product = await this.prisma.product.findUnique({
            where: { id: dto.productId },
        });

        if (!product || product.deletedAt) throw new NotFoundException('Product not found');
        if (product.approvalStatus !== 'APPROVED') throw new BadRequestException('Product is not approved');
        if (!product.transactionTypes.includes('BUY')) {
            throw new BadRequestException('Product is not available for purchase');
        }
        if (product.sellerId === buyerId) throw new ForbiddenException('Cannot buy your own product');
        if (product.quantity < dto.quantity) {
            throw new BadRequestException(`Only ${product.quantity} unit(s) available`);
        }

        const subtotal = parseFloat(product.price.toString()) * dto.quantity;
        const pricing = await this.pricing.calculatePurchase(subtotal);

        // Reserve inventory + create order atomically
        const reservedUntil = await this.inventory.reserve(dto.productId, dto.quantity);

        const order = await this.prisma.order.create({
            data: {
                buyerId,
                productId: dto.productId,
                quantity: dto.quantity,
                subtotal: pricing.subtotal,
                serviceFee: pricing.serviceFee,
                totalAmount: pricing.totalAmount,
                sellerAmount: pricing.sellerAmount,
                platformAmount: pricing.platformAmount,
                feePercentage: pricing.feePercentage,
                reservedUntil,
            },
            include: {
                product: { select: PRODUCT_SNAPSHOT },
            },
        });

        return order;
    }

    // ── Buyer orders ───────────────────────────────────────────────────────────
    async findByBuyer(buyerId: string, query: OrdersQueryDto) {
        const { page = 1, limit = 20 } = query;
        const [total, data] = await Promise.all([
            this.prisma.order.count({ where: { buyerId } }),
            this.prisma.order.findMany({
                where: { buyerId },
                include: { product: { select: PRODUCT_SNAPSHOT } },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);
        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    // ── Seller orders ──────────────────────────────────────────────────────────
    async findBySeller(sellerId: string, query: OrdersQueryDto) {
        const { page = 1, limit = 20 } = query;
        const where = { product: { sellerId } };
        const [total, data] = await Promise.all([
            this.prisma.order.count({ where }),
            this.prisma.order.findMany({
                where,
                include: {
                    product: { select: PRODUCT_SNAPSHOT },
                    buyer: { select: { id: true, name: true, avatarUrl: true } },
                },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);
        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    // ── Detail ─────────────────────────────────────────────────────────────────
    async findById(orderId: string, requesterId: string) {
        const order = await this.prisma.order.findUnique({
            where: { id: orderId },
            include: {
                product: { select: { ...PRODUCT_SNAPSHOT, sellerId: true } },
                buyer: { select: { id: true, name: true, avatarUrl: true } },
            },
        });
        if (!order) throw new NotFoundException('Order not found');

        const isBuyer = order.buyerId === requesterId;
        const isSeller = order.product.sellerId === requesterId;
        if (!isBuyer && !isSeller) throw new ForbiddenException('Not a participant in this order');

        return order;
    }

    // ── Cancel ─────────────────────────────────────────────────────────────────
    async cancel(orderId: string, requesterId: string) {
        const order = await this.prisma.order.findUnique({
            where: { id: orderId },
            include: { product: { select: { sellerId: true } } },
        });
        if (!order) throw new NotFoundException('Order not found');

        const isBuyer = order.buyerId === requesterId;
        const isSeller = order.product.sellerId === requesterId;
        if (!isBuyer && !isSeller) throw new ForbiddenException('Not a participant in this order');
        if (order.status !== 'PENDING') {
            throw new BadRequestException('Only PENDING orders can be cancelled');
        }

        await this.inventory.release(orderId);
        return this.prisma.order.update({
            where: { id: orderId },
            data: { status: 'CANCELLED' },
            include: { product: { select: PRODUCT_SNAPSHOT } },
        });
    }

    // ── Complete ───────────────────────────────────────────────────────────────
    async complete(orderId: string, requesterId: string) {
        const order = await this.prisma.order.findUnique({
            where: { id: orderId },
            include: { product: { select: { sellerId: true, condition: true } } },
        });
        if (!order) throw new NotFoundException('Order not found');
        if (order.product.sellerId !== requesterId) {
            throw new ForbiddenException('Only the seller can mark an order as completed');
        }
        if (order.status !== 'PAID') {
            throw new BadRequestException('Only PAID orders can be marked as completed');
        }

        const updated = await this.prisma.order.update({
            where: { id: orderId },
            data: { status: 'COMPLETED' },
            include: { product: { select: PRODUCT_SNAPSHOT } },
        });

        await Promise.all([
            this.ecoScore.recordEcoImpact({
                userId: order.buyerId,
                condition: order.product.condition ?? 'GOOD',
                transactionType: 'BUY',
                transactionId: orderId,
            }),
            this.notifications.send(order.buyerId, {
                type: 'ORDER_COMPLETED',
                title: 'Order completed',
                body: 'Your order has been marked as completed. Please leave a review!',
                data: { orderId },
            }),
        ]);

        return updated;
    }

    async onPaymentComplete(orderId: string): Promise<void> {
        const order = await this.prisma.order.findUnique({
            where: { id: orderId },
            include: { product: { select: { sellerId: true } } },
        });
        if (!order) return;

        await this.prisma.order.update({ where: { id: orderId }, data: { status: 'PAID' } });

        const sellerAmount = parseFloat(order.sellerAmount.toString());
        const platformAmount = parseFloat(order.platformAmount.toString());

        await Promise.all([
            this.walletLedger.creditSaleIfMissing({
                sellerId: order.product.sellerId,
                amount: sellerAmount,
                entryType: 'SALE_CREDIT',
                referenceType: 'ORDER',
                referenceId: orderId,
            }),
            this.walletLedger.creditPlatformFeeIfMissing({
                amount: platformAmount,
                referenceType: 'ORDER',
                referenceId: orderId,
            }),
            this.notifications.send(order.product.sellerId, {
                type: 'ORDER_PAID',
                title: 'New order payment received',
                body: `Your product has been purchased. NPR ${sellerAmount} credited to wallet.`,
                data: { orderId },
            }),
        ]);
    }
}
