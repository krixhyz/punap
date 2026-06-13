import { BadRequestException, Injectable, Logger, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { Prisma, Wallet, PayoutRequest } from '../generated/prisma/client';
import { LedgerEntryType } from '../generated/prisma/enums';
import { NotificationsService } from '../notifications/notifications.service';
import { RequestPayoutDto } from './dto/request-payout.dto';
import { RejectPayoutDto } from './dto/reject-payout.dto';
import { MarkPaidDto } from './dto/mark-paid.dto';
import { LedgerQueryDto } from './dto/ledger-query.dto';

@Injectable()
export class WalletLedgerService {
    private readonly logger = new Logger(WalletLedgerService.name);

    constructor(
        private readonly prisma: PrismaService,
        private readonly notifications: NotificationsService,
    ) {}

    // ── Wallet helpers ──────────────────────────────────────────────────────────

    async getOrCreateUserWallet(userId: string): Promise<Wallet> {
        return this.prisma.wallet.upsert({
            where: { userId },
            create: { userId, walletType: 'USER', currency: 'NPR' },
            update: {},
        });
    }

    private async getPlatformWallet(): Promise<Wallet> {
        const wallet = await this.prisma.wallet.findFirst({ where: { walletType: 'PLATFORM' } });
        if (wallet) return wallet;
        return this.prisma.wallet.create({ data: { walletType: 'PLATFORM', currency: 'NPR' } });
    }

    // ── Core ledger write (idempotent) ─────────────────────────────────────────

    private async postCredit(opts: {
        walletId: string;
        amount: number;
        entryType: LedgerEntryType;
        referenceType: string;
        referenceId: string;
    }): Promise<void> {
        const key = `${opts.entryType}:${opts.referenceType}:${opts.referenceId}`;
        try {
            await this.prisma.$transaction(async (tx) => {
                const wallet = await tx.wallet.findUniqueOrThrow({ where: { id: opts.walletId } });
                const newBalance =
                    parseFloat(wallet.availableBalance.toString()) + opts.amount;

                await tx.walletLedgerEntry.create({
                    data: {
                        walletId: opts.walletId,
                        direction: 'CREDIT',
                        entryType: opts.entryType,
                        amount: opts.amount,
                        balanceAfter: newBalance,
                        referenceType: opts.referenceType,
                        referenceId: opts.referenceId,
                        idempotencyKey: key,
                    },
                });

                await tx.wallet.update({
                    where: { id: opts.walletId },
                    data: {
                        availableBalance: { increment: opts.amount },
                        lifetimeCredit: { increment: opts.amount },
                    },
                });
            });
        } catch (e) {
            if ((e as Prisma.PrismaClientKnownRequestError).code === 'P2002') return;
            throw e;
        }
    }

    private async postDebit(opts: {
        walletId: string;
        amount: number;
        entryType: LedgerEntryType;
        referenceType: string;
        referenceId: string;
        metadata?: Record<string, unknown>;
    }): Promise<void> {
        const key = `${opts.entryType}:${opts.referenceType}:${opts.referenceId}`;
        try {
            await this.prisma.$transaction(async (tx) => {
                const wallet = await tx.wallet.findUniqueOrThrow({ where: { id: opts.walletId } });
                const current = parseFloat(wallet.availableBalance.toString());
                if (current < opts.amount) throw new BadRequestException('Insufficient balance');
                const newBalance = current - opts.amount;

                await tx.walletLedgerEntry.create({
                    data: {
                        walletId: opts.walletId,
                        direction: 'DEBIT',
                        entryType: opts.entryType,
                        amount: opts.amount,
                        balanceAfter: newBalance,
                        referenceType: opts.referenceType,
                        referenceId: opts.referenceId,
                        idempotencyKey: key,
                        metadata: opts.metadata ? (opts.metadata as Prisma.InputJsonValue) : Prisma.DbNull,
                    },
                });

                await tx.wallet.update({
                    where: { id: opts.walletId },
                    data: {
                        availableBalance: { decrement: opts.amount },
                        lifetimeDebit: { increment: opts.amount },
                    },
                });
            });
        } catch (e) {
            if ((e as Prisma.PrismaClientKnownRequestError).code === 'P2002') return;
            throw e;
        }
    }

    // ── Public credit helpers ───────────────────────────────────────────────────

    async creditSaleIfMissing(opts: {
        sellerId: string;
        amount: number;
        entryType: LedgerEntryType;
        referenceType: string;
        referenceId: string;
    }): Promise<void> {
        const wallet = await this.getOrCreateUserWallet(opts.sellerId);
        await this.postCredit({
            walletId: wallet.id,
            amount: opts.amount,
            entryType: opts.entryType,
            referenceType: opts.referenceType,
            referenceId: opts.referenceId,
        });
    }

    async creditPlatformFeeIfMissing(opts: {
        amount: number;
        referenceType: string;
        referenceId: string;
    }): Promise<void> {
        const wallet = await this.getPlatformWallet();
        await this.postCredit({
            walletId: wallet.id,
            amount: opts.amount,
            entryType: 'PLATFORM_FEE',
            referenceType: opts.referenceType,
            referenceId: opts.referenceId,
        });
    }

    // ── Swap fund release ──────────────────────────────────────────────────────

    async releaseSwapFunds(swapRequestId: string): Promise<void> {
        const swap = await this.prisma.swapRequest.findUnique({
            where: { id: swapRequestId },
        });
        if (!swap) return;
        if (swap.moneyDirection === 'NONE') return;

        const payment = await this.prisma.payment.findFirst({
            where: { sourceType: 'SWAP', sourceId: swapRequestId, status: 'COMPLETE' },
        });
        if (!payment) {
            this.logger.warn(`No complete payment found for swap ${swapRequestId} — skipping fund release`);
            return;
        }

        // REQUESTER_OFFERS_CASH → owner receives; OWNER_ASKS_CASH → requester receives
        const recipientId =
            swap.moneyDirection === 'REQUESTER_OFFERS_CASH' ? swap.ownerId : swap.requesterId;
        const sellerAmount = parseFloat(payment.sellerAmount.toString());
        const platformAmount = parseFloat(payment.platformAmount.toString());

        await Promise.all([
            this.creditSaleIfMissing({
                sellerId: recipientId,
                amount: sellerAmount,
                entryType: 'SWAP_CREDIT',
                referenceType: 'SWAP',
                referenceId: swapRequestId,
            }),
            this.creditPlatformFeeIfMissing({
                amount: platformAmount,
                referenceType: 'SWAP',
                referenceId: swapRequestId,
            }),
        ]);
    }

    // ── Payouts ────────────────────────────────────────────────────────────────

    async requestPayout(userId: string, dto: RequestPayoutDto): Promise<PayoutRequest> {
        const wallet = await this.getOrCreateUserWallet(userId);
        const available = parseFloat(wallet.availableBalance.toString());
        if (available < dto.amount) {
            throw new BadRequestException(
                `Insufficient balance. Available: ${available}, requested: ${dto.amount}`,
            );
        }

        // Debit from available, add to pending
        await this.postDebit({
            walletId: wallet.id,
            amount: dto.amount,
            entryType: 'PAYOUT_HOLD',
            referenceType: 'PAYOUT',
            referenceId: wallet.id + ':' + Date.now(),
        });

        const payout = await this.prisma.$transaction(async (tx) => {
            await tx.wallet.update({
                where: { id: wallet.id },
                data: { pendingPayoutBalance: { increment: dto.amount } },
            });
            return tx.payoutRequest.create({
                data: { userId, walletId: wallet.id, amount: dto.amount, note: dto.note },
            });
        });

        return payout;
    }

    async rejectPayout(payoutRequestId: string, adminId: string, dto: RejectPayoutDto): Promise<PayoutRequest> {
        const payout = await this.prisma.payoutRequest.findUnique({
            where: { id: payoutRequestId },
        });
        if (!payout) throw new NotFoundException('Payout request not found');
        if (payout.status !== 'PENDING') {
            throw new BadRequestException(`Payout is already ${payout.status}`);
        }

        const amount = parseFloat(payout.amount.toString());

        // Reverse hold: return to available, remove from pending
        await this.postCredit({
            walletId: payout.walletId,
            amount,
            entryType: 'PAYOUT_RELEASE',
            referenceType: 'PAYOUT',
            referenceId: payoutRequestId,
        });

        const rejected = await this.prisma.$transaction(async (tx) => {
            await tx.wallet.update({
                where: { id: payout.walletId },
                data: { pendingPayoutBalance: { decrement: amount } },
            });
            return tx.payoutRequest.update({
                where: { id: payoutRequestId },
                data: {
                    status: 'REJECTED',
                    rejectionReason: dto.reason,
                    processedBy: adminId,
                    rejectedAt: new Date(),
                },
            });
        });

        await this.notifications.send(payout.userId, {
            type: 'PAYOUT_REJECTED',
            title: 'Payout request rejected',
            body: `Your payout request of NPR ${amount} was rejected. Reason: ${dto.reason}`,
            data: { payoutRequestId },
        });

        return rejected;
    }

    async markPayoutPaid(payoutRequestId: string, adminId: string, dto: MarkPaidDto): Promise<PayoutRequest> {
        const payout = await this.prisma.payoutRequest.findUnique({
            where: { id: payoutRequestId },
        });
        if (!payout) throw new NotFoundException('Payout request not found');
        if (payout.status !== 'PENDING') {
            throw new BadRequestException(`Payout is already ${payout.status}`);
        }

        const amount = parseFloat(payout.amount.toString());

        const paid = await this.prisma.$transaction(async (tx) => {
            await tx.wallet.update({
                where: { id: payout.walletId },
                data: {
                    pendingPayoutBalance: { decrement: amount },
                    lifetimeDebit: { increment: amount },
                },
            });
            return tx.payoutRequest.update({
                where: { id: payoutRequestId },
                data: {
                    status: 'PAID',
                    payoutReference: dto.reference,
                    processedBy: adminId,
                    approvedAt: new Date(),
                    paidAt: new Date(),
                },
            });
        });

        await this.notifications.send(payout.userId, {
            type: 'PAYOUT_PAID',
            title: 'Payout sent',
            body: `Your payout of NPR ${amount} has been processed. Reference: ${dto.reference}`,
            data: { payoutRequestId },
        });

        return paid;
    }

    // ── Query helpers ───────────────────────────────────────────────────────────

    async getMyWallet(userId: string) {
        return this.getOrCreateUserWallet(userId);
    }

    async getLedger(userId: string, query: LedgerQueryDto) {
        const { page = 1, limit = 20 } = query;
        const wallet = await this.getOrCreateUserWallet(userId);

        const [total, data] = await Promise.all([
            this.prisma.walletLedgerEntry.count({ where: { walletId: wallet.id } }),
            this.prisma.walletLedgerEntry.findMany({
                where: { walletId: wallet.id },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);
        return { wallet, data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    async getMyPayouts(userId: string) {
        return this.prisma.payoutRequest.findMany({
            where: { userId },
            orderBy: { createdAt: 'desc' },
        });
    }

    async adminListPayouts(status?: string) {
        return this.prisma.payoutRequest.findMany({
            where: status
                ? { status: status as 'PENDING' | 'APPROVED' | 'REJECTED' | 'PAID' }
                : undefined,
            include: { user: { select: { id: true, name: true, email: true } } },
            orderBy: { createdAt: 'desc' },
        });
    }
}
