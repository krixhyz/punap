import {
    BadRequestException,
    ForbiddenException,
    Injectable,
    Logger,
    NotFoundException,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { PrismaService } from '../prisma/prisma.service';
import { KhaltiService } from './khalti.service';
import { EsewaService } from './esewa.service';
import { PaymentResolutionService } from './payment-resolution.service';
import { v4 as uuidv4 } from 'uuid';

type Gateway = 'khalti' | 'esewa';

@Injectable()
export class PaymentsService {
    private readonly logger = new Logger(PaymentsService.name);

    constructor(
        private readonly prisma: PrismaService,
        private readonly khalti: KhaltiService,
        private readonly esewa: EsewaService,
        private readonly resolution: PaymentResolutionService,
        private readonly config: ConfigService,
    ) {}

    private frontendUrl(): string {
        return this.config.get<string>('FRONTEND_URL', 'http://localhost:5173');
    }

    // ── Initiate for Order ────────────────────────────────────────────────────
    async initiateForOrder(orderId: string, userId: string, gateway: Gateway) {
        const order = await this.prisma.order.findUnique({
            where: { id: orderId },
            include: { product: { select: { title: true, sellerId: true } }, buyer: true },
        });
        if (!order) throw new NotFoundException('Order not found');
        if (order.buyerId !== userId) throw new ForbiddenException('Not the buyer of this order');
        if (order.status !== 'PENDING') {
            throw new BadRequestException('Order is not in PENDING status');
        }

        const transactionUuid = uuidv4();
        const totalAmount = parseFloat(order.totalAmount.toString());

        const payment = await this.prisma.payment.create({
            data: {
                userId,
                sourceType: 'ORDER',
                sourceId: orderId,
                gateway: gateway.toUpperCase() as any,
                amount: order.subtotal,
                serviceFee: order.serviceFee,
                totalAmount: order.totalAmount,
                sellerAmount: order.sellerAmount,
                platformAmount: order.platformAmount,
                feePercentage: order.feePercentage,
                requestPayload: { transactionUuid },
            },
        });

        if (gateway === 'khalti') {
            const result = await this.khalti.initiatePayment({
                return_url: `${this.frontendUrl()}/payment/success`,
                website_url: this.frontendUrl(),
                amount: this.khalti.toPaisa(totalAmount),
                purchase_order_id: payment.id,
                purchase_order_name: order.product.title,
                customer_info: { name: order.buyer.name, email: order.buyer.email },
            });
            await this.prisma.payment.update({ where: { id: payment.id }, data: { pidx: result.pidx } });
            return { paymentUrl: result.paymentUrl, paymentId: payment.id };
        } else {
            const fields = this.esewa.getFormFields({
                amount: parseFloat(order.subtotal.toString()),
                taxAmount: parseFloat(order.serviceFee.toString()),
                totalAmount,
                transactionUuid,
                successUrl: `${this.frontendUrl()}/payment/success`,
                failureUrl: `${this.frontendUrl()}/payment/failure`,
            });
            await this.prisma.payment.update({
                where: { id: payment.id },
                data: { requestPayload: fields as any },
            });
            return { paymentUrl: this.esewa.getPaymentUrl(), formFields: fields, paymentId: payment.id };
        }
    }

    // ── Initiate for Rental ───────────────────────────────────────────────────
    async initiateForRental(rentalBookingId: string, userId: string, gateway: Gateway) {
        const booking = await this.prisma.rentalBooking.findUnique({
            where: { id: rentalBookingId },
            include: { product: { select: { title: true } }, renter: true },
        });
        if (!booking) throw new NotFoundException('Rental booking not found');
        if (booking.renterId !== userId) throw new ForbiddenException('Not the renter of this booking');
        if (booking.status !== 'PENDING_PAYMENT') {
            throw new BadRequestException('Booking is not in PENDING_PAYMENT status');
        }

        const transactionUuid = uuidv4();
        const totalAmount = parseFloat(booking.totalAmount.toString());

        const payment = await this.prisma.payment.create({
            data: {
                userId,
                sourceType: 'RENTAL',
                sourceId: rentalBookingId,
                gateway: gateway.toUpperCase() as any,
                amount: booking.rentFare,
                serviceFee: booking.serviceFee,
                totalAmount: booking.totalAmount,
                sellerAmount: booking.sellerAmount,
                platformAmount: booking.platformAmount,
                feePercentage: booking.feePercentage,
                requestPayload: { transactionUuid },
            },
        });

        if (gateway === 'khalti') {
            const result = await this.khalti.initiatePayment({
                return_url: `${this.frontendUrl()}/payment/success`,
                website_url: this.frontendUrl(),
                amount: this.khalti.toPaisa(totalAmount),
                purchase_order_id: payment.id,
                purchase_order_name: booking.product.title,
                customer_info: { name: booking.renter.name, email: booking.renter.email },
            });
            await this.prisma.payment.update({ where: { id: payment.id }, data: { pidx: result.pidx } });
            return { paymentUrl: result.paymentUrl, paymentId: payment.id };
        } else {
            const fields = this.esewa.getFormFields({
                amount: parseFloat(booking.rentFare.toString()),
                taxAmount: parseFloat(booking.serviceFee.toString()),
                totalAmount,
                transactionUuid,
                successUrl: `${this.frontendUrl()}/payment/success`,
                failureUrl: `${this.frontendUrl()}/payment/failure`,
            });
            await this.prisma.payment.update({
                where: { id: payment.id },
                data: { requestPayload: fields as any },
            });
            return { paymentUrl: this.esewa.getPaymentUrl(), formFields: fields, paymentId: payment.id };
        }
    }

    // ── Initiate for Swap (cash-difference) ───────────────────────────────────
    async initiateForSwap(swapRequestId: string, userId: string, gateway: Gateway) {
        const swap = await this.prisma.swapRequest.findUnique({
            where: { id: swapRequestId },
            include: {
                product: { select: { title: true } },
                requester: true,
                owner: true,
            },
        });
        if (!swap) throw new NotFoundException('Swap request not found');
        if (swap.requesterId !== userId && swap.ownerId !== userId) {
            throw new ForbiddenException('Not a participant in this swap');
        }
        if (swap.status !== 'AWAITING_PAYMENT') {
            throw new BadRequestException('Swap is not awaiting payment');
        }

        // Determine who pays: the person who owes cash
        let payAmount = 0;
        if (swap.moneyDirection === 'OWNER_ASKS_CASH' && swap.requesterId === userId) {
            payAmount = parseFloat(swap.offeredAmount.toString());
        } else if (swap.moneyDirection === 'REQUESTER_OFFERS_CASH' && swap.requesterId === userId) {
            payAmount = parseFloat(swap.offeredAmount.toString());
        } else if (swap.moneyDirection === 'OWNER_ASKS_CASH' && swap.ownerId === userId) {
            payAmount = parseFloat(swap.askingAmount.toString());
        }

        if (payAmount <= 0) throw new BadRequestException('No cash payment required for this swap');

        const transactionUuid = uuidv4();

        const payment = await this.prisma.payment.create({
            data: {
                userId,
                sourceType: 'SWAP',
                sourceId: swapRequestId,
                gateway: gateway.toUpperCase() as any,
                amount: payAmount,
                totalAmount: payAmount,
                requestPayload: { transactionUuid },
            },
        });

        if (gateway === 'khalti') {
            const payer = swap.requesterId === userId ? swap.requester : swap.owner;
            const result = await this.khalti.initiatePayment({
                return_url: `${this.frontendUrl()}/payment/success`,
                website_url: this.frontendUrl(),
                amount: this.khalti.toPaisa(payAmount),
                purchase_order_id: payment.id,
                purchase_order_name: `Swap: ${swap.product.title}`,
                customer_info: { name: payer.name, email: payer.email },
            });
            await this.prisma.payment.update({ where: { id: payment.id }, data: { pidx: result.pidx } });
            return { paymentUrl: result.paymentUrl, paymentId: payment.id };
        } else {
            const fields = this.esewa.getFormFields({
                amount: payAmount,
                taxAmount: 0,
                totalAmount: payAmount,
                transactionUuid,
                successUrl: `${this.frontendUrl()}/payment/success`,
                failureUrl: `${this.frontendUrl()}/payment/failure`,
            });
            await this.prisma.payment.update({
                where: { id: payment.id },
                data: { requestPayload: fields as any },
            });
            return { paymentUrl: this.esewa.getPaymentUrl(), formFields: fields, paymentId: payment.id };
        }
    }

    // ── Khalti callback ───────────────────────────────────────────────────────
    async handleKhaltiCallback(pidx: string): Promise<{ success: boolean }> {
        const payment = await this.prisma.payment.findUnique({ where: { pidx } });
        if (!payment) return { success: false };

        // Idempotency
        if (payment.status === 'COMPLETE') return { success: true };

        try {
            const lookup = await this.khalti.lookupPayment(pidx);
            if (lookup.status === 'Completed') {
                await this.resolution.resolve(payment.id);
                return { success: true };
            } else {
                await this.prisma.payment.update({ where: { id: payment.id }, data: { status: 'FAILED' } });
                return { success: false };
            }
        } catch (err) {
            this.logger.error(`Khalti callback error for pidx ${pidx}: ${err}`);
            return { success: false };
        }
    }

    // ── eSewa callback ────────────────────────────────────────────────────────
    async handleEsewaCallback(payload: Record<string, string>): Promise<{ success: boolean }> {
        const valid = this.esewa.verifyCallback(payload);
        if (!valid) {
            this.logger.warn('eSewa callback signature verification failed');
            return { success: false };
        }

        // Locate payment by transaction_uuid stored in requestPayload
        const transactionUuid = payload['transaction_uuid'];
        const payment = await this.prisma.payment.findFirst({
            where: {
                requestPayload: { path: ['transaction_uuid'], equals: transactionUuid },
            },
        });
        if (!payment) return { success: false };
        if (payment.status === 'COMPLETE') return { success: true };

        await this.resolution.resolve(payment.id);
        return { success: true };
    }

    // ── Get payment detail ────────────────────────────────────────────────────
    async findById(paymentId: string, userId: string) {
        const payment = await this.prisma.payment.findUnique({ where: { id: paymentId } });
        if (!payment) throw new NotFoundException('Payment not found');
        if (payment.userId !== userId) throw new ForbiddenException('Not your payment');
        return payment;
    }
}
