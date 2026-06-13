import {
    BadRequestException,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { NotificationsService } from '../notifications/notifications.service';
import { CreateDisputeDto } from './dto/create-dispute.dto';
import { ResolveDisputeDto } from './dto/resolve-dispute.dto';

const PARTICIPANT_SELECT = { id: true, name: true, avatarUrl: true, email: true };

@Injectable()
export class DisputesService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly notifications: NotificationsService,
    ) {}

    async create(complainantId: string, dto: CreateDisputeDto) {
        if (complainantId === dto.respondentId) {
            throw new BadRequestException('Cannot dispute with yourself');
        }

        const dispute = await this.prisma.dispute.create({
            data: {
                complainantId,
                respondentId: dto.respondentId,
                transactionType: dto.transactionType,
                orderId: dto.orderId,
                rentalBookingId: dto.rentalBookingId,
                swapId: dto.swapId,
                subject: dto.subject,
                description: dto.description,
                evidencePhotos: dto.evidencePhotos ?? [],
                rentalClaimAmount: dto.rentalClaimAmount,
                status: 'OPEN',
            },
            include: {
                complainant: { select: PARTICIPANT_SELECT },
                respondent: { select: PARTICIPANT_SELECT },
            },
        });

        await this.notifications.send(dto.respondentId, {
            type: 'DISPUTE_OPENED',
            title: 'A dispute has been opened against you',
            body: `A dispute has been filed: "${dto.subject}". Our team will review it.`,
            data: { disputeId: dispute.id },
        });

        return dispute;
    }

    async findMyDisputes(userId: string) {
        return this.prisma.dispute.findMany({
            where: {
                OR: [{ complainantId: userId }, { respondentId: userId }],
            },
            include: {
                complainant: { select: PARTICIPANT_SELECT },
                respondent: { select: PARTICIPANT_SELECT },
            },
            orderBy: { createdAt: 'desc' },
        });
    }

    async findById(disputeId: string, userId: string) {
        const dispute = await this.prisma.dispute.findUnique({
            where: { id: disputeId },
            include: {
                complainant: { select: PARTICIPANT_SELECT },
                respondent: { select: PARTICIPANT_SELECT },
            },
        });
        if (!dispute) throw new NotFoundException('Dispute not found');
        if (dispute.complainantId !== userId && dispute.respondentId !== userId) {
            throw new ForbiddenException('Not a participant in this dispute');
        }
        return dispute;
    }

    async adminFindAll(status?: string) {
        return this.prisma.dispute.findMany({
            where: status
                ? { status: status as 'OPEN' | 'IN_REVIEW' | 'RESOLVED' | 'DISMISSED' }
                : undefined,
            include: {
                complainant: { select: PARTICIPANT_SELECT },
                respondent: { select: PARTICIPANT_SELECT },
            },
            orderBy: { createdAt: 'desc' },
        });
    }

    async adminResolve(disputeId: string, adminId: string, dto: ResolveDisputeDto) {
        const dispute = await this.prisma.dispute.findUnique({ where: { id: disputeId } });
        if (!dispute) throw new NotFoundException('Dispute not found');
        if (dispute.status === 'RESOLVED' || dispute.status === 'DISMISSED') {
            throw new BadRequestException(`Dispute is already ${dispute.status}`);
        }

        const resolved = await this.prisma.dispute.update({
            where: { id: disputeId },
            data: {
                status: 'RESOLVED',
                resolution: dto.resolution,
                favoredUserId: dto.favoredUserId,
                rentalClaimAmount: dto.rentalClaimAmount,
                resolvedBy: adminId,
                resolvedAt: new Date(),
            },
        });

        await Promise.all([
            this.notifications.send(dispute.complainantId, {
                type: 'DISPUTE_RESOLVED',
                title: 'Your dispute has been resolved',
                body: dto.resolution,
                data: { disputeId },
            }),
            this.notifications.send(dispute.respondentId, {
                type: 'DISPUTE_RESOLVED',
                title: 'A dispute against you has been resolved',
                body: dto.resolution,
                data: { disputeId },
            }),
        ]);

        return resolved;
    }

    async adminDismiss(disputeId: string, adminId: string) {
        const dispute = await this.prisma.dispute.findUnique({ where: { id: disputeId } });
        if (!dispute) throw new NotFoundException('Dispute not found');
        if (dispute.status === 'RESOLVED' || dispute.status === 'DISMISSED') {
            throw new BadRequestException(`Dispute is already ${dispute.status}`);
        }

        const dismissed = await this.prisma.dispute.update({
            where: { id: disputeId },
            data: { status: 'DISMISSED', resolvedBy: adminId, resolvedAt: new Date() },
        });

        await Promise.all([
            this.notifications.send(dispute.complainantId, {
                type: 'DISPUTE_DISMISSED',
                title: 'Your dispute was dismissed',
                body: 'Our team reviewed and dismissed the dispute.',
                data: { disputeId },
            }),
            this.notifications.send(dispute.respondentId, {
                type: 'DISPUTE_DISMISSED',
                title: 'A dispute against you was dismissed',
                body: 'The dispute filed against you has been dismissed.',
                data: { disputeId },
            }),
        ]);

        return dismissed;
    }

    async adminMarkInReview(disputeId: string) {
        const dispute = await this.prisma.dispute.findUnique({ where: { id: disputeId } });
        if (!dispute) throw new NotFoundException('Dispute not found');
        if (dispute.status !== 'OPEN') {
            throw new BadRequestException('Only OPEN disputes can be moved to IN_REVIEW');
        }
        return this.prisma.dispute.update({
            where: { id: disputeId },
            data: { status: 'IN_REVIEW' },
        });
    }
}
