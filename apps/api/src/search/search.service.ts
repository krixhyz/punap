import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { SearchQueryDto } from './dto/search-query.dto';
import { Prisma } from '../generated/prisma/client';

const SELLER_SELECT = {
    id: true,
    name: true,
    avatarUrl: true,
    totalEcoScore: true,
    ecoLevel: true,
};

@Injectable()
export class SearchService {
    constructor(private readonly prisma: PrismaService) {}

    async search(query: SearchQueryDto) {
        const {
            q, categoryId, transactionType, condition,
            minPrice, maxPrice, provinceId, cityId,
            sortBy = 'newest', page = 1, limit = 20,
        } = query;

        const where: Prisma.ProductWhereInput = {
            deletedAt: null,
            approvalStatus: 'APPROVED',
        };

        if (q) where.title = { contains: q, mode: 'insensitive' };
        if (categoryId) where.categoryId = parseInt(categoryId, 10);
        if (condition) where.condition = condition as never;
        if (provinceId) where.provinceId = parseInt(provinceId, 10);
        if (cityId) where.cityId = parseInt(cityId, 10);
        if (transactionType) where.transactionTypes = { has: transactionType };
        if (minPrice !== undefined || maxPrice !== undefined) {
            where.price = {};
            if (minPrice !== undefined) (where.price as Prisma.DecimalFilter).gte = minPrice;
            if (maxPrice !== undefined) (where.price as Prisma.DecimalFilter).lte = maxPrice;
        }

        const orderBy = this.buildOrderBy(sortBy);

        const [total, items] = await Promise.all([
            this.prisma.product.count({ where }),
            this.prisma.product.findMany({
                where,
                include: {
                    seller: { select: SELLER_SELECT },
                    category: true,
                    reviews: { select: { rating: true } },
                },
                orderBy,
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);

        const data = items.map((p) => {
            const reviews = p.reviews as { rating: number }[];
            const avgRating = reviews.length
                ? reviews.reduce((s, r) => s + r.rating, 0) / reviews.length
                : null;
            const { reviews: _r, ...rest } = p;
            return { ...rest, avgRating, reviewCount: reviews.length };
        });

        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    async suggestions(q: string) {
        if (!q || q.length < 2) return [];

        const results = await this.prisma.product.findMany({
            where: {
                title: { contains: q, mode: 'insensitive' },
                deletedAt: null,
                approvalStatus: 'APPROVED',
            },
            select: { id: true, title: true },
            orderBy: { createdAt: 'desc' },
            take: 8,
        });

        return results;
    }

    private buildOrderBy(sortBy: string): Prisma.ProductOrderByWithRelationInput {
        switch (sortBy) {
            case 'price_asc': return { price: 'asc' };
            case 'price_desc': return { price: 'desc' };
            case 'eco_score': return { seller: { totalEcoScore: 'desc' } };
            default: return { createdAt: 'desc' };
        }
    }
}
