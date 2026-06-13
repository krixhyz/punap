import {
    BadRequestException,
    ConflictException,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { Cron, CronExpression } from '@nestjs/schedule';
import { PrismaService } from '../prisma/prisma.service';
import { CheckoutPricingService } from '../orders/checkout-pricing.service';
import { RentalDepositService } from './rental-deposit.service';
import { WalletLedgerService } from '../wallet/wallet-ledger.service';
import { EcoScoreService } from '../eco-score/eco-score.service';
import { NotificationsService } from '../notifications/notifications.service';
import { BookRentalDto } from './dto/book-rental.dto';
import { RentalsQueryDto } from './dto/rentals-query.dto';

const BOOKING_TTL_MINUTES = 30;

const PRODUCT_SNAPSHOT = {
    id: true,
    title: true,
    images: true,
    price: true,
    rentFare: true,
    rentType: true,
    sellerId: true,
    seller: { select: { id: true, name: true, avatarUrl: true } },
};

function calcDuration(startDate: Date, endDate: Date): number {
    const ms = endDate.getTime() - startDate.getTime();
    return Math.ceil(ms / (1000 * 60 * 60 * 24));
}

function calcRentFeeForDuration(rentFare: number, rentType: string, durationDays: number): number {
    switch (rentType) {
        case 'WEEKLY':
            return rentFare * Math.ceil(durationDays / 7);
        case 'MONTHLY':
            return rentFare * Math.ceil(durationDays / 30);
        default: // DAILY
            return rentFare * durationDays;
    }
}

@Injectable()
export class RentalsService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly pricing: CheckoutPricingService,
        private readonly depositService: RentalDepositService,
        private readonly walletLedger: WalletLedgerService,
        private readonly ecoScore: EcoScoreService,
        private readonly notifications: NotificationsService,
    ) {}

    // ── Book ──────────────────────────────────────────────────────────────────
    async book(renterId: string, dto: BookRentalDto) {
        const product = await this.prisma.product.findUnique({
            where: { id: dto.productId },
        });
        if (!product || product.deletedAt) throw new NotFoundException('Product not found');
        if (product.approvalStatus !== 'APPROVED') {
            throw new BadRequestException('Product is not approved for rental');
        }
        if (!product.transactionTypes.includes('RENT')) {
            throw new BadRequestException('Product is not available for rent');
        }
        if (product.sellerId === renterId) {
            throw new ForbiddenException('Cannot rent your own product');
        }
        if (!product.rentFare || !product.rentType) {
            throw new BadRequestException('Product has no rental configuration');
        }

        const startDate = new Date(dto.startDate);
        const endDate = new Date(dto.endDate);
        if (endDate <= startDate) {
            throw new BadRequestException('End date must be after start date');
        }

        // Overlap check
        const overlap = await this.prisma.rentalBooking.findFirst({
            where: {
                productId: dto.productId,
                status: { in: ['PENDING_PAYMENT', 'ACTIVE'] },
                startDate: { lt: endDate },
                endDate: { gt: startDate },
            },
        });
        if (overlap) throw new ConflictException('Selected dates overlap with an existing booking');

        const durationDays = calcDuration(startDate, endDate);
        const rentFare = parseFloat(product.rentFare.toString());
        const deposit = product.rentDeposit ? parseFloat(product.rentDeposit.toString()) : 0;
        const totalRentFee = calcRentFeeForDuration(rentFare, product.rentType, durationDays);

        const pricingResult = await this.pricing.calculateRent(totalRentFee, deposit);

        const reservedUntil = new Date(Date.now() + BOOKING_TTL_MINUTES * 60 * 1000);

        const booking = await this.prisma.rentalBooking.create({
            data: {
                renterId,
                ownerId: product.sellerId,
                productId: dto.productId,
                startDate,
                endDate,
                duration: durationDays,
                rentFare: pricingResult.subtotal,
                rentDeposit: deposit,
                rentType: product.rentType,
                totalAmount: pricingResult.totalAmount,
                serviceFee: pricingResult.serviceFee,
                sellerAmount: pricingResult.sellerAmount,
                platformAmount: pricingResult.platformAmount,
                feePercentage: pricingResult.feePercentage,
                stockReserved: true,
                reservedUntil,
            },
            include: {
                product: { select: PRODUCT_SNAPSHOT },
                renter: { select: { id: true, name: true, avatarUrl: true } },
            },
        });
        return booking;
    }

    // ── Renter's bookings ─────────────────────────────────────────────────────
    async findByRenter(renterId: string, query: RentalsQueryDto) {
        const { page = 1, limit = 20 } = query;
        const [total, data] = await Promise.all([
            this.prisma.rentalBooking.count({ where: { renterId } }),
            this.prisma.rentalBooking.findMany({
                where: { renterId },
                include: { product: { select: PRODUCT_SNAPSHOT } },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);
        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    // ── Owner's bookings ──────────────────────────────────────────────────────
    async findByOwner(ownerId: string, query: RentalsQueryDto) {
        const { page = 1, limit = 20 } = query;
        const [total, data] = await Promise.all([
            this.prisma.rentalBooking.count({ where: { ownerId } }),
            this.prisma.rentalBooking.findMany({
                where: { ownerId },
                include: {
                    product: { select: PRODUCT_SNAPSHOT },
                    renter: { select: { id: true, name: true, avatarUrl: true } },
                },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);
        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    // ── Detail ────────────────────────────────────────────────────────────────
    async findById(rentalId: string, requesterId: string) {
        const booking = await this.prisma.rentalBooking.findUnique({
            where: { id: rentalId },
            include: {
                product: { select: { ...PRODUCT_SNAPSHOT, sellerId: true } },
                renter: { select: { id: true, name: true, avatarUrl: true } },
                deposit: true,
            },
        });
        if (!booking) throw new NotFoundException('Rental booking not found');
        if (booking.renterId !== requesterId && booking.ownerId !== requesterId) {
            throw new ForbiddenException('Not a participant in this rental');
        }
        return booking;
    }

    // ── Cancel ────────────────────────────────────────────────────────────────
    async cancel(rentalId: string, requesterId: string) {
        const booking = await this.prisma.rentalBooking.findUnique({
            where: { id: rentalId },
        });
        if (!booking) throw new NotFoundException('Rental booking not found');
        if (booking.renterId !== requesterId && booking.ownerId !== requesterId) {
            throw new ForbiddenException('Not a participant in this rental');
        }
        if (booking.status !== 'PENDING_PAYMENT') {
            throw new BadRequestException('Only PENDING_PAYMENT bookings can be cancelled');
        }
        return this.prisma.rentalBooking.update({
            where: { id: rentalId },
            data: { status: 'CANCELLED', stockReserved: false },
        });
    }

    // ── Request return ────────────────────────────────────────────────────────
    async requestReturn(rentalId: string, renterId: string, evidenceUrls: string[]) {
        const booking = await this.prisma.rentalBooking.findUnique({
            where: { id: rentalId },
        });
        if (!booking) throw new NotFoundException('Rental booking not found');
        if (booking.renterId !== renterId) {
            throw new ForbiddenException('Only the renter can request a return');
        }
        if (booking.status !== 'ACTIVE') {
            throw new BadRequestException('Rental must be ACTIVE to request return');
        }
        return this.prisma.rentalBooking.update({
            where: { id: rentalId },
            data: {
                status: 'RETURN_REQUESTED',
                returnRequestedAt: new Date(),
                evidencePhotos: evidenceUrls,
            },
        });
    }

    // ── Confirm return ────────────────────────────────────────────────────────
    async confirmReturn(rentalId: string, ownerId: string) {
        const booking = await this.prisma.rentalBooking.findUnique({
            where: { id: rentalId },
            include: { deposit: true, product: { select: { condition: true } } },
        });
        if (!booking) throw new NotFoundException('Rental booking not found');
        if (booking.ownerId !== ownerId) {
            throw new ForbiddenException('Only the owner can confirm a return');
        }
        if (booking.status !== 'RETURN_REQUESTED') {
            throw new BadRequestException('Rental must be in RETURN_REQUESTED status');
        }

        await this.prisma.rentalBooking.update({
            where: { id: rentalId },
            data: { status: 'COMPLETED', returnedAt: new Date() },
        });

        if (booking.deposit && booking.deposit.status === 'HELD') {
            await this.depositService.initiateRefund(booking.deposit.id);
        }

        await this.ecoScore.recordEcoImpact({
            userId: booking.renterId,
            condition: booking.product?.condition ?? 'GOOD',
            transactionType: 'RENT',
            transactionId: rentalId,
        });

        return this.prisma.rentalBooking.findUnique({
            where: { id: rentalId },
            include: { deposit: true, product: { select: PRODUCT_SNAPSHOT } },
        });
    }

    async onPaymentComplete(rentalBookingId: string): Promise<void> {
        const booking = await this.prisma.rentalBooking.findUnique({
            where: { id: rentalBookingId },
        });
        if (!booking) return;

        await this.prisma.rentalBooking.update({
            where: { id: rentalBookingId },
            data: { status: 'ACTIVE', reservedUntil: null },
        });

        if (booking.rentDeposit && parseFloat(booking.rentDeposit.toString()) > 0) {
            await this.depositService.hold(rentalBookingId, parseFloat(booking.rentDeposit.toString()));
        }

        const sellerAmount = parseFloat(booking.sellerAmount.toString());
        const platformAmount = parseFloat(booking.platformAmount.toString());

        await Promise.all([
            this.walletLedger.creditSaleIfMissing({
                sellerId: booking.ownerId,
                amount: sellerAmount,
                entryType: 'RENTAL_CREDIT',
                referenceType: 'RENTAL',
                referenceId: rentalBookingId,
            }),
            this.walletLedger.creditPlatformFeeIfMissing({
                amount: platformAmount,
                referenceType: 'RENTAL',
                referenceId: rentalBookingId,
            }),
            this.notifications.send(booking.ownerId, {
                type: 'RENTAL_ACTIVE',
                title: 'Rental payment received',
                body: `A rental booking is now active. NPR ${sellerAmount} credited to wallet.`,
                data: { rentalBookingId },
            }),
        ]);
    }

    // ── Cron: expire stale PENDING_PAYMENT bookings every 5 min ──────────────
    @Cron(CronExpression.EVERY_5_MINUTES)
    async releaseExpiredReservations(): Promise<void> {
        const expired = await this.prisma.rentalBooking.findMany({
            where: {
                status: 'PENDING_PAYMENT',
                reservedUntil: { lt: new Date() },
            },
            select: { id: true },
        });
        if (expired.length === 0) return;

        await this.prisma.rentalBooking.updateMany({
            where: { id: { in: expired.map((b) => b.id) } },
            data: { status: 'CANCELLED', stockReserved: false },
        });
    }
}
