import {
    BadRequestException,
    ConflictException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import * as bcrypt from 'bcrypt';
import { PrismaService } from '../prisma/prisma.service';
import { ProductsService } from '../products/products.service';
import { DisputesService } from '../disputes/disputes.service';
import { WalletLedgerService } from '../wallet/wallet-ledger.service';
import { NotificationsService } from '../notifications/notifications.service';
import { AdminUsersQueryDto } from './dto/admin-users-query.dto';
import { SuspendUserDto } from './dto/suspend-user.dto';
import { CreateAdminDto } from './dto/create-admin.dto';
import { UpdateSettingDto } from './dto/update-setting.dto';
import { RejectPayoutDto } from '../wallet/dto/reject-payout.dto';
import { MarkPaidDto } from '../wallet/dto/mark-paid.dto';
import { ResolveDisputeDto } from '../disputes/dto/resolve-dispute.dto';

const USER_SUMMARY = {
    id: true,
    name: true,
    email: true,
    role: true,
    accountStatus: true,
    profileStatus: true,
    ecoLevel: true,
    totalEcoScore: true,
    createdAt: true,
};

@Injectable()
export class AdminService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly productsService: ProductsService,
        private readonly disputesService: DisputesService,
        private readonly walletService: WalletLedgerService,
        private readonly notifications: NotificationsService,
    ) {}

    // ── Dashboard ──────────────────────────────────────────────────────────────

    async getStats() {
        const [
            totalUsers,
            totalProducts,
            activeRentals,
            openDisputes,
            pendingPayouts,
            platformWallet,
        ] = await Promise.all([
            this.prisma.user.count(),
            this.prisma.product.count({ where: { deletedAt: null } }),
            this.prisma.rentalBooking.count({ where: { status: 'ACTIVE' } }),
            this.prisma.dispute.count({ where: { status: { in: ['OPEN', 'IN_REVIEW'] } } }),
            this.prisma.payoutRequest.count({ where: { status: 'PENDING' } }),
            this.prisma.wallet.findFirst({ where: { walletType: 'PLATFORM' } }),
        ]);

        const now = new Date();
        const firstOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        const monthRevenue = await this.prisma.payment.aggregate({
            _sum: { platformAmount: true },
            where: { status: 'COMPLETE', createdAt: { gte: firstOfMonth } },
        });

        return {
            totalUsers,
            totalProducts,
            activeRentals,
            openDisputes,
            pendingPayouts,
            platformBalance: platformWallet?.availableBalance ?? 0,
            monthlyRevenue: monthRevenue._sum.platformAmount ?? 0,
        };
    }

    // ── Users ──────────────────────────────────────────────────────────────────

    async listUsers(query: AdminUsersQueryDto) {
        const { role, status, q, page = 1, limit = 20 } = query;

        const where: Record<string, unknown> = {};
        if (role) where.role = role;
        if (status) where.accountStatus = status;
        if (q) where.OR = [
            { name: { contains: q, mode: 'insensitive' } },
            { email: { contains: q, mode: 'insensitive' } },
        ];

        const [total, data] = await Promise.all([
            this.prisma.user.count({ where }),
            this.prisma.user.findMany({
                where,
                select: USER_SUMMARY,
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);

        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    async getUserDetail(userId: string) {
        const user = await this.prisma.user.findUnique({
            where: { id: userId },
            include: {
                wallet: true,
                ecoScores: { take: 5, orderBy: { createdAt: 'desc' } },
            },
        });
        if (!user) throw new NotFoundException('User not found');
        return user;
    }

    async suspendUser(userId: string, dto: SuspendUserDto) {
        const user = await this.prisma.user.findUnique({ where: { id: userId } });
        if (!user) throw new NotFoundException('User not found');
        if (user.role === 'SUPER_ADMIN') {
            throw new BadRequestException('Cannot suspend a super admin');
        }
        const updated = await this.prisma.user.update({
            where: { id: userId },
            data: { accountStatus: 'SUSPENDED' },
        });
        await this.notifications.send(userId, {
            type: 'ACCOUNT_SUSPENDED',
            title: 'Account suspended',
            body: dto.reason ?? 'Your account has been suspended. Contact support.',
            data: {},
        });
        return updated;
    }

    async banUser(userId: string, dto: SuspendUserDto) {
        const user = await this.prisma.user.findUnique({ where: { id: userId } });
        if (!user) throw new NotFoundException('User not found');
        if (user.role === 'SUPER_ADMIN') {
            throw new BadRequestException('Cannot ban a super admin');
        }
        const updated = await this.prisma.user.update({
            where: { id: userId },
            data: { accountStatus: 'BANNED' },
        });
        await this.notifications.send(userId, {
            type: 'ACCOUNT_BANNED',
            title: 'Account banned',
            body: dto.reason ?? 'Your account has been banned.',
            data: {},
        });
        return updated;
    }

    async activateUser(userId: string) {
        const user = await this.prisma.user.findUnique({ where: { id: userId } });
        if (!user) throw new NotFoundException('User not found');
        return this.prisma.user.update({
            where: { id: userId },
            data: { accountStatus: 'ACTIVE' },
        });
    }

    async approveUserProfile(userId: string) {
        const user = await this.prisma.user.findUnique({ where: { id: userId } });
        if (!user) throw new NotFoundException('User not found');
        return this.prisma.user.update({
            where: { id: userId },
            data: { profileStatus: 'VERIFIED' },
        });
    }

    async createAdmin(dto: CreateAdminDto) {
        const existing = await this.prisma.user.findUnique({ where: { email: dto.email } });
        if (existing) throw new ConflictException('Email already in use');

        const hashedPassword = await bcrypt.hash(dto.password, 12);
        return this.prisma.user.create({
            data: {
                name: dto.name,
                email: dto.email,
                password: hashedPassword,
                role: 'ADMIN',
                accountStatus: 'ACTIVE',
                emailVerifiedAt: new Date(),
            },
            select: USER_SUMMARY,
        });
    }

    async revokeAdmin(userId: string) {
        const user = await this.prisma.user.findUnique({ where: { id: userId } });
        if (!user) throw new NotFoundException('User not found');
        if (user.role !== 'ADMIN') throw new BadRequestException('User is not an admin');
        return this.prisma.user.update({
            where: { id: userId },
            data: { role: 'USER' },
            select: USER_SUMMARY,
        });
    }

    // ── Products ───────────────────────────────────────────────────────────────

    async listProducts(approvalStatus?: string, page = 1, limit = 20) {
        const where: Record<string, unknown> = { deletedAt: null };
        if (approvalStatus) where.approvalStatus = approvalStatus;

        const [total, data] = await Promise.all([
            this.prisma.product.count({ where }),
            this.prisma.product.findMany({
                where,
                include: { seller: { select: { id: true, name: true, email: true } }, category: true },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);

        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    approveProduct(productId: string) {
        return this.productsService.approve(productId);
    }

    rejectProduct(productId: string, reason?: string) {
        return this.productsService.reject(productId, reason);
    }

    // ── Disputes ───────────────────────────────────────────────────────────────

    listDisputes(status?: string) {
        return this.disputesService.adminFindAll(status);
    }

    takeDispute(disputeId: string) {
        return this.disputesService.adminMarkInReview(disputeId);
    }

    resolveDispute(disputeId: string, adminId: string, dto: ResolveDisputeDto) {
        return this.disputesService.adminResolve(disputeId, adminId, dto);
    }

    dismissDispute(disputeId: string, adminId: string) {
        return this.disputesService.adminDismiss(disputeId, adminId);
    }

    // ── Payouts ────────────────────────────────────────────────────────────────

    listPayouts(status?: string) {
        return this.walletService.adminListPayouts(status);
    }

    rejectPayout(payoutId: string, adminId: string, dto: RejectPayoutDto) {
        return this.walletService.rejectPayout(payoutId, adminId, dto);
    }

    markPayoutPaid(payoutId: string, adminId: string, dto: MarkPaidDto) {
        return this.walletService.markPayoutPaid(payoutId, adminId, dto);
    }

    // ── Platform Settings ──────────────────────────────────────────────────────

    async listSettings() {
        return this.prisma.platformSetting.findMany({ orderBy: { key: 'asc' } });
    }

    async updateSetting(key: string, dto: UpdateSettingDto) {
        return this.prisma.platformSetting.upsert({
            where: { key },
            create: { key, value: String(dto.value) },
            update: { value: String(dto.value) },
        });
    }
}
