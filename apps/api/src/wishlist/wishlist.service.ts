import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';

@Injectable()
export class WishlistService {
    constructor(private readonly prisma: PrismaService) {}

    async toggle(userId: string, productId: string) {
        const product = await this.prisma.product.findUnique({ where: { id: productId } });
        if (!product || product.deletedAt) throw new NotFoundException('Product not found');

        const existing = await this.prisma.wishlist.findUnique({
            where: { userId_productId: { userId, productId } },
        });

        if (existing) {
            await this.prisma.wishlist.delete({
                where: { userId_productId: { userId, productId } },
            });
            return { wishlisted: false };
        }

        await this.prisma.wishlist.create({ data: { userId, productId } });
        return { wishlisted: true };
    }

    async findMy(userId: string, page = 1, limit = 20) {
        const [total, items] = await Promise.all([
            this.prisma.wishlist.count({ where: { userId } }),
            this.prisma.wishlist.findMany({
                where: { userId },
                include: {
                    product: {
                        include: {
                            seller: { select: { id: true, name: true, avatarUrl: true } },
                            category: true,
                        },
                    },
                },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);

        return { data: items, total, page, limit, totalPages: Math.ceil(total / limit) };
    }
}
