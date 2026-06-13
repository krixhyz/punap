import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { NotificationsGateway } from './notifications.gateway';
import { NotificationsQueryDto } from './dto/notifications-query.dto';
import { Notification } from '../generated/prisma/client';

export interface SendNotificationOpts {
    type: string;
    title: string;
    body: string;
    data?: Record<string, string | number | boolean | null>;
}

@Injectable()
export class NotificationsService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly gateway: NotificationsGateway,
    ) {}

    async send(userId: string, opts: SendNotificationOpts): Promise<Notification> {
        const notification = await this.prisma.notification.create({
            data: {
                userId,
                type: opts.type,
                title: opts.title,
                body: opts.body,
                data: opts.data ?? {},
            },
        });

        this.gateway.emitToUser(userId, 'notification', notification);

        return notification;
    }

    async findMine(userId: string, query: NotificationsQueryDto) {
        const { page = 1, limit = 20, unreadOnly } = query;
        const where: Record<string, unknown> = { userId };
        if (unreadOnly) where.readAt = null;

        const [total, data, unreadCount] = await Promise.all([
            this.prisma.notification.count({ where }),
            this.prisma.notification.findMany({
                where,
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
            this.prisma.notification.count({ where: { userId, readAt: null } }),
        ]);

        return { data, total, unreadCount, page, limit, totalPages: Math.ceil(total / limit) };
    }

    async markRead(notificationId: string, userId: string) {
        return this.prisma.notification.updateMany({
            where: { id: notificationId, userId },
            data: { readAt: new Date() },
        });
    }

    async markAllRead(userId: string) {
        return this.prisma.notification.updateMany({
            where: { userId, readAt: null },
            data: { readAt: new Date() },
        });
    }
}
