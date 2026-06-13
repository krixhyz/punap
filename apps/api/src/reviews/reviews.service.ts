import {
    BadRequestException,
    ConflictException,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { CreateReviewDto } from './dto/create-review.dto';
import { ReviewsQueryDto } from './dto/reviews-query.dto';

@Injectable()
export class ReviewsService {
    constructor(private readonly prisma: PrismaService) {}

    async create(reviewerId: string, dto: CreateReviewDto) {
        // Validate the reviewer was actually a participant in the referenced transaction
        await this.validateParticipant(reviewerId, dto);

        // Check uniqueness per transaction (Prisma unique constraint handles it,
        // but give a clear error message here)
        const existing = await this.findExisting(reviewerId, dto);
        if (existing) throw new ConflictException('You have already reviewed this transaction');

        return this.prisma.review.create({
            data: {
                reviewerId,
                subjectId: dto.subjectId,
                productId: dto.productId,
                transactionType: dto.transactionType,
                orderId: dto.orderId,
                rentalBookingId: dto.rentalBookingId,
                swapId: dto.swapId,
                rating: dto.rating,
                body: dto.body,
            },
            include: {
                reviewer: { select: { id: true, name: true, avatarUrl: true } },
                subject: { select: { id: true, name: true, avatarUrl: true } },
            },
        });
    }

    async findAll(query: ReviewsQueryDto) {
        const { page = 1, limit = 20, subjectId, productId, transactionType } = query;
        const where: Record<string, unknown> = {};
        if (subjectId) where.subjectId = subjectId;
        if (productId) where.productId = productId;
        if (transactionType) where.transactionType = transactionType;

        const [total, data] = await Promise.all([
            this.prisma.review.count({ where }),
            this.prisma.review.findMany({
                where,
                include: {
                    reviewer: { select: { id: true, name: true, avatarUrl: true } },
                },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);
        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    async findById(reviewId: string) {
        const review = await this.prisma.review.findUnique({
            where: { id: reviewId },
            include: {
                reviewer: { select: { id: true, name: true, avatarUrl: true } },
                subject: { select: { id: true, name: true, avatarUrl: true } },
            },
        });
        if (!review) throw new NotFoundException('Review not found');
        return review;
    }

    private async findExisting(reviewerId: string, dto: CreateReviewDto) {
        if (dto.orderId) {
            return this.prisma.review.findFirst({
                where: { reviewerId, orderId: dto.orderId },
            });
        }
        if (dto.rentalBookingId) {
            return this.prisma.review.findFirst({
                where: { reviewerId, rentalBookingId: dto.rentalBookingId },
            });
        }
        if (dto.swapId) {
            return this.prisma.review.findFirst({
                where: { reviewerId, swapId: dto.swapId },
            });
        }
        return null;
    }

    private async validateParticipant(reviewerId: string, dto: CreateReviewDto): Promise<void> {
        if (dto.transactionType === 'BUY' && dto.orderId) {
            const order = await this.prisma.order.findUnique({ where: { id: dto.orderId } });
            if (!order) throw new NotFoundException('Order not found');
            if (order.status !== 'COMPLETED' && order.status !== 'PAID') {
                throw new BadRequestException('Can only review completed orders');
            }
            if (order.buyerId !== reviewerId) {
                throw new ForbiddenException('Only the buyer can review this order');
            }
        } else if (dto.transactionType === 'RENT' && dto.rentalBookingId) {
            const booking = await this.prisma.rentalBooking.findUnique({
                where: { id: dto.rentalBookingId },
            });
            if (!booking) throw new NotFoundException('Rental booking not found');
            if (booking.status !== 'COMPLETED') {
                throw new BadRequestException('Can only review completed rentals');
            }
            if (booking.renterId !== reviewerId && booking.ownerId !== reviewerId) {
                throw new ForbiddenException('Not a participant in this rental');
            }
        } else if (dto.transactionType === 'SWAP' && dto.swapId) {
            const swap = await this.prisma.swapRequest.findUnique({ where: { id: dto.swapId } });
            if (!swap) throw new NotFoundException('Swap not found');
            if (swap.status !== 'COMPLETED') {
                throw new BadRequestException('Can only review completed swaps');
            }
            if (swap.ownerId !== reviewerId && swap.requesterId !== reviewerId) {
                throw new ForbiddenException('Not a participant in this swap');
            }
        } else {
            throw new BadRequestException('Must provide orderId, rentalBookingId, or swapId matching transactionType');
        }
    }
}
