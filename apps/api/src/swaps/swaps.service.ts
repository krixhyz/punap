import {
    BadRequestException,
    ConflictException,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { SwapOrderService } from './swap-order.service';
import { CreateSwapDto } from './dto/create-swap.dto';
import { CounterOfferDto } from './dto/counter-offer.dto';
import { SwapsQueryDto } from './dto/swaps-query.dto';

const PRODUCT_SNAP = {
    id: true,
    title: true,
    images: true,
    price: true,
    status: true,
    seller: { select: { id: true, name: true, avatarUrl: true } },
};

@Injectable()
export class SwapsService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly swapOrder: SwapOrderService,
    ) {}

    // ── Propose ───────────────────────────────────────────────────────────────
    async propose(requesterId: string, dto: CreateSwapDto) {
        const [product, offeredProduct] = await Promise.all([
            this.prisma.product.findUnique({ where: { id: dto.productId } }),
            this.prisma.product.findUnique({ where: { id: dto.offeredProductId } }),
        ]);

        if (!product || product.deletedAt || product.approvalStatus !== 'APPROVED') {
            throw new NotFoundException('Target product not found or not approved');
        }
        if (!offeredProduct || offeredProduct.deletedAt || offeredProduct.approvalStatus !== 'APPROVED') {
            throw new NotFoundException('Offered product not found or not approved');
        }
        if (offeredProduct.sellerId !== requesterId) {
            throw new ForbiddenException('You do not own the offered product');
        }
        if (product.sellerId === requesterId) {
            throw new ForbiddenException('Cannot swap with your own product');
        }
        if (!product.transactionTypes.includes('SWAP')) {
            throw new BadRequestException('Target product is not available for swap');
        }

        return this.prisma.$transaction(async (tx) => {
            const swapRequest = await tx.swapRequest.create({
                data: {
                    productId: dto.productId,
                    offeredProductId: dto.offeredProductId,
                    ownerId: product.sellerId,
                    requesterId,
                    message: dto.message,
                    offeredAmount: dto.offeredAmount ?? 0,
                    askingAmount: dto.askedAmount ?? 0,
                    moneyDirection: dto.moneyDirection ?? 'NONE',
                    status: 'PENDING',
                },
                include: {
                    product: { select: PRODUCT_SNAP },
                    offeredProduct: { select: PRODUCT_SNAP },
                },
            });

            await tx.swapNegotiationEvent.create({
                data: {
                    swapRequestId: swapRequest.id,
                    actorId: requesterId,
                    type: 'INITIAL_OFFER',
                    offeredAmount: dto.offeredAmount ?? 0,
                    askingAmount: dto.askedAmount ?? 0,
                    moneyDirection: dto.moneyDirection ?? 'NONE',
                    message: dto.message,
                },
            });

            return swapRequest;
        });
    }

    // ── Counter ───────────────────────────────────────────────────────────────
    async counter(swapId: string, actorId: string, dto: CounterOfferDto) {
        const swap = await this.prisma.swapRequest.findUnique({ where: { id: swapId } });
        if (!swap) throw new NotFoundException('Swap not found');
        if (swap.ownerId !== actorId && swap.requesterId !== actorId) {
            throw new ForbiddenException('Not a participant');
        }
        if (!['PENDING', 'COUNTERED'].includes(swap.status)) {
            throw new ConflictException(`Cannot counter a swap in ${swap.status} status`);
        }

        const updates: Record<string, unknown> = { status: 'COUNTERED' };
        if (dto.offeredAmount !== undefined) updates.offeredAmount = dto.offeredAmount;
        if (dto.askedAmount !== undefined) updates.askingAmount = dto.askedAmount;
        if (dto.moneyDirection !== undefined) updates.moneyDirection = dto.moneyDirection;

        return this.prisma.$transaction(async (tx) => {
            const updated = await tx.swapRequest.update({
                where: { id: swapId },
                data: updates,
                include: {
                    product: { select: PRODUCT_SNAP },
                    offeredProduct: { select: PRODUCT_SNAP },
                },
            });
            await tx.swapNegotiationEvent.create({
                data: {
                    swapRequestId: swapId,
                    actorId,
                    type: 'COUNTER_OFFER',
                    offeredAmount: dto.offeredAmount,
                    askingAmount: dto.askedAmount,
                    moneyDirection: dto.moneyDirection,
                    message: dto.message,
                },
            });
            return updated;
        });
    }

    // ── Accept ────────────────────────────────────────────────────────────────
    async accept(swapId: string, ownerId: string) {
        const swap = await this.prisma.swapRequest.findUnique({ where: { id: swapId } });
        if (!swap) throw new NotFoundException('Swap not found');
        if (swap.ownerId !== ownerId) throw new ForbiddenException('Only the product owner can accept');
        if (!['PENDING', 'COUNTERED'].includes(swap.status)) {
            throw new ConflictException(`Cannot accept a swap in ${swap.status} status`);
        }

        const cashInvolved = swap.moneyDirection !== 'NONE' &&
            (parseFloat(swap.offeredAmount.toString()) > 0 || parseFloat(swap.askingAmount.toString()) > 0);

        const newStatus = cashInvolved ? 'AWAITING_PAYMENT' : 'CONFIRMATION_PENDING';

        return this.prisma.$transaction(async (tx) => {
            const updated = await tx.swapRequest.update({
                where: { id: swapId },
                data: { status: newStatus },
                include: {
                    product: { select: PRODUCT_SNAP },
                    offeredProduct: { select: PRODUCT_SNAP },
                },
            });

            await tx.swapNegotiationEvent.create({
                data: { swapRequestId: swapId, actorId: ownerId, type: 'ACCEPT', message: 'Swap accepted' },
            });

            // Create confirmation record for no-cash swaps immediately
            if (newStatus === 'CONFIRMATION_PENDING') {
                await tx.swapOrderConfirmation.upsert({
                    where: { swapRequestId: swapId },
                    create: { swapRequestId: swapId },
                    update: {},
                });
            }

            return updated;
        });
    }

    // ── Reject ────────────────────────────────────────────────────────────────
    async reject(swapId: string, ownerId: string) {
        const swap = await this.prisma.swapRequest.findUnique({ where: { id: swapId } });
        if (!swap) throw new NotFoundException('Swap not found');
        if (swap.ownerId !== ownerId) throw new ForbiddenException('Only the product owner can reject');
        if (!['PENDING', 'COUNTERED'].includes(swap.status)) {
            throw new ConflictException(`Cannot reject a swap in ${swap.status} status`);
        }

        return this.prisma.$transaction(async (tx) => {
            const updated = await tx.swapRequest.update({
                where: { id: swapId },
                data: { status: 'REJECTED' },
            });
            await tx.swapNegotiationEvent.create({
                data: { swapRequestId: swapId, actorId: ownerId, type: 'REJECT' },
            });
            return updated;
        });
    }

    // ── Cancel ────────────────────────────────────────────────────────────────
    async cancel(swapId: string, requesterId: string) {
        const swap = await this.prisma.swapRequest.findUnique({ where: { id: swapId } });
        if (!swap) throw new NotFoundException('Swap not found');
        if (swap.requesterId !== requesterId) {
            throw new ForbiddenException('Only the requester can cancel');
        }
        if (['COMPLETED', 'REJECTED', 'CANCELLED'].includes(swap.status)) {
            throw new ConflictException(`Cannot cancel a swap in ${swap.status} status`);
        }

        return this.prisma.$transaction(async (tx) => {
            const updated = await tx.swapRequest.update({
                where: { id: swapId },
                data: { status: 'CANCELLED' },
            });
            await tx.swapNegotiationEvent.create({
                data: { swapRequestId: swapId, actorId: requesterId, type: 'CANCEL' },
            });
            return updated;
        });
    }

    // ── Confirm received ──────────────────────────────────────────────────────
    async confirmReceived(swapId: string, userId: string) {
        const swap = await this.prisma.swapRequest.findUnique({
            where: { id: swapId },
            include: { orderConfirmation: true },
        });
        if (!swap) throw new NotFoundException('Swap not found');
        if (swap.ownerId !== userId && swap.requesterId !== userId) {
            throw new ForbiddenException('Not a participant');
        }
        if (swap.status !== 'CONFIRMATION_PENDING') {
            throw new ConflictException('Swap is not in CONFIRMATION_PENDING status');
        }

        const isOwner = swap.ownerId === userId;
        const updateData: Record<string, unknown> = {};
        if (isOwner) {
            if (swap.orderConfirmation?.ownerConfirmedAt) {
                throw new ConflictException('You have already confirmed receipt');
            }
            updateData.ownerConfirmedAt = new Date();
        } else {
            if (swap.orderConfirmation?.requesterConfirmedAt) {
                throw new ConflictException('You have already confirmed receipt');
            }
            updateData.requesterConfirmedAt = new Date();
        }

        let confirmation = await this.prisma.swapOrderConfirmation.update({
            where: { swapRequestId: swapId },
            data: updateData,
        });

        // Check if both confirmed → complete the swap
        if (confirmation.ownerConfirmedAt && confirmation.requesterConfirmedAt) {
            await this.swapOrder.complete(swapId);
        }

        return this.prisma.swapRequest.findUnique({
            where: { id: swapId },
            include: {
                product: { select: PRODUCT_SNAP },
                offeredProduct: { select: PRODUCT_SNAP },
                orderConfirmation: true,
            },
        });
    }

    // ── List (for current user) ────────────────────────────────────────────────
    async findAll(userId: string, query: SwapsQueryDto) {
        const { page = 1, limit = 20, status } = query;
        const where: Record<string, unknown> = {
            OR: [{ ownerId: userId }, { requesterId: userId }],
        };
        if (status) where.status = status;

        const [total, data] = await Promise.all([
            this.prisma.swapRequest.count({ where }),
            this.prisma.swapRequest.findMany({
                where,
                include: {
                    product: { select: PRODUCT_SNAP },
                    offeredProduct: { select: PRODUCT_SNAP },
                    negotiationEvents: {
                        orderBy: { createdAt: 'desc' },
                        take: 1,
                        select: { type: true, message: true, createdAt: true },
                    },
                },
                orderBy: { updatedAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);
        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    // ── Detail ────────────────────────────────────────────────────────────────
    async findById(swapId: string, userId: string) {
        const swap = await this.prisma.swapRequest.findUnique({
            where: { id: swapId },
            include: {
                product: { select: { ...PRODUCT_SNAP, sellerId: true } },
                offeredProduct: { select: { ...PRODUCT_SNAP, sellerId: true } },
                owner: { select: { id: true, name: true, avatarUrl: true } },
                requester: { select: { id: true, name: true, avatarUrl: true } },
                orderConfirmation: true,
            },
        });
        if (!swap) throw new NotFoundException('Swap not found');
        if (swap.ownerId !== userId && swap.requesterId !== userId) {
            throw new ForbiddenException('Not a participant');
        }
        return swap;
    }

    // ── Negotiation events ────────────────────────────────────────────────────
    async getEvents(swapId: string, userId: string) {
        const swap = await this.prisma.swapRequest.findUnique({
            where: { id: swapId },
            select: { ownerId: true, requesterId: true },
        });
        if (!swap) throw new NotFoundException('Swap not found');
        if (swap.ownerId !== userId && swap.requesterId !== userId) {
            throw new ForbiddenException('Not a participant');
        }
        return this.prisma.swapNegotiationEvent.findMany({
            where: { swapRequestId: swapId },
            include: { actor: { select: { id: true, name: true, avatarUrl: true } } },
            orderBy: { createdAt: 'asc' },
        });
    }
}
