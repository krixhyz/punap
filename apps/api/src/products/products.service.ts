import {
    BadRequestException,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { StorageService } from '../storage/storage.service';
import { CreateProductDto } from './dto/create-product.dto';
import { ProductFiltersDto } from './dto/product-filters.dto';

const DEFAULT_PAGE_SIZE = 20;

const SELLER_SELECT = {
    id: true,
    name: true,
    avatarUrl: true,
    totalEcoScore: true,
    ecoLevel: true,
};

@Injectable()
export class ProductsService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly storage: StorageService,
    ) {}

    async findAll(filters: ProductFiltersDto) {
        const {
            categoryId, transactionType, condition, minPrice, maxPrice,
            provinceId, cityId, keyword, sellerId,
            page = 1, limit = DEFAULT_PAGE_SIZE,
        } = filters;

        const where: Record<string, unknown> = {
            deletedAt: null,
            approvalStatus: 'APPROVED',
        };

        if (categoryId) where.categoryId = categoryId;
        if (condition) where.condition = condition;
        if (provinceId) where.provinceId = provinceId;
        if (cityId) where.cityId = cityId;
        if (sellerId) where.sellerId = sellerId;
        if (transactionType) where.transactionTypes = { has: transactionType };
        if (keyword) where.title = { contains: keyword, mode: 'insensitive' };
        if (minPrice !== undefined || maxPrice !== undefined) {
            where.price = {};
            if (minPrice !== undefined) (where.price as Record<string, number>).gte = minPrice;
            if (maxPrice !== undefined) (where.price as Record<string, number>).lte = maxPrice;
        }

        const [total, items] = await Promise.all([
            this.prisma.product.count({ where }),
            this.prisma.product.findMany({
                where,
                include: { seller: { select: SELLER_SELECT }, category: true },
                orderBy: { createdAt: 'desc' },
                skip: (page - 1) * limit,
                take: limit,
            }),
        ]);

        return { data: items, total, page, limit, totalPages: Math.ceil(total / limit) };
    }

    async findById(id: string, viewerId?: string) {
        const product = await this.prisma.product.findUnique({
            where: { id },
            include: {
                seller: { select: SELLER_SELECT },
                category: true,
                reviews: { take: 5, orderBy: { createdAt: 'desc' } },
            },
        });
        if (!product || product.deletedAt) throw new NotFoundException('Product not found');

        // Side-effect: record recently viewed for authenticated users
        if (viewerId) {
            await this.prisma.recentlyViewed.upsert({
                where: { userId_productId: { userId: viewerId, productId: id } },
                create: { userId: viewerId, productId: id },
                update: { viewedAt: new Date() },
            });
        }

        return product;
    }

    async findMy(userId: string) {
        return this.prisma.product.findMany({
            where: { sellerId: userId, deletedAt: null },
            include: { category: true },
            orderBy: { createdAt: 'desc' },
        });
    }

    async create(userId: string, dto: CreateProductDto, files: Express.Multer.File[]) {
        const imageUrls = files.length > 0 ? await this.storage.uploadMany(files) : [];

        return this.prisma.product.create({
            data: {
                sellerId: userId,
                title: dto.title,
                description: dto.description,
                price: dto.price,
                quantity: dto.quantity,
                condition: dto.condition as never,
                categoryId: dto.categoryId,
                images: imageUrls,
                transactionTypes: dto.transactionTypes,
                provinceId: dto.provinceId,
                cityId: dto.cityId,
                rentFare: dto.rentFare,
                rentDeposit: dto.rentDeposit,
                rentType: dto.rentType as never,
                availableFrom: dto.availableFrom ? new Date(dto.availableFrom) : undefined,
                availableDuration: dto.availableDuration,
            },
        });
    }

    async update(
        id: string,
        userId: string,
        dto: Partial<CreateProductDto>,
        files: Express.Multer.File[],
    ) {
        const product = await this.prisma.product.findUnique({ where: { id } });
        if (!product || product.deletedAt) throw new NotFoundException('Product not found');
        if (product.sellerId !== userId) throw new ForbiddenException('Not the product owner');

        let images = product.images;
        if (files.length > 0) {
            // Delete old images and upload new ones
            await Promise.all(product.images.map((url) => this.storage.delete(url)));
            images = await this.storage.uploadMany(files);
        }

        return this.prisma.product.update({
            where: { id },
            data: {
                title: dto.title,
                description: dto.description,
                price: dto.price,
                quantity: dto.quantity,
                condition: dto.condition as never,
                categoryId: dto.categoryId,
                images,
                transactionTypes: dto.transactionTypes,
                provinceId: dto.provinceId,
                cityId: dto.cityId,
                rentFare: dto.rentFare,
                rentDeposit: dto.rentDeposit,
                rentType: dto.rentType as never,
                availableFrom: dto.availableFrom ? new Date(dto.availableFrom) : undefined,
                availableDuration: dto.availableDuration,
            },
        });
    }

    async softDelete(id: string, userId: string) {
        const product = await this.prisma.product.findUnique({ where: { id } });
        if (!product || product.deletedAt) throw new NotFoundException('Product not found');
        if (product.sellerId !== userId) throw new ForbiddenException('Not the product owner');

        // Guard: block if active rental booking or unpaid order
        const [activeRental, unpaidOrder] = await Promise.all([
            this.prisma.rentalBooking.count({
                where: { productId: id, status: { in: ['ACTIVE', 'PENDING_PAYMENT'] } },
            }),
            this.prisma.order.count({
                where: { productId: id, status: 'PENDING' },
            }),
        ]);
        if (activeRental > 0) throw new BadRequestException('Product has active rental bookings');
        if (unpaidOrder > 0) throw new BadRequestException('Product has pending orders');

        return this.prisma.product.update({
            where: { id },
            data: { deletedAt: new Date() },
        });
    }

    async approve(id: string) {
        await this.assertExists(id);
        return this.prisma.product.update({
            where: { id },
            data: { approvalStatus: 'APPROVED' },
        });
    }

    async reject(id: string, reason?: string) {
        await this.assertExists(id);
        return this.prisma.product.update({
            where: { id },
            data: {
                approvalStatus: 'REJECTED',
                description: reason
                    ? `[REJECTED: ${reason}]\n${(await this.prisma.product.findUnique({ where: { id }, select: { description: true } }))?.description ?? ''}`
                    : undefined,
            },
        });
    }

    async getRecentlyViewed(userId: string) {
        const rows = await this.prisma.recentlyViewed.findMany({
            where: { userId },
            include: {
                product: {
                    include: {
                        seller: { select: { id: true, name: true, avatarUrl: true } },
                        category: true,
                    },
                },
            },
            orderBy: { viewedAt: 'desc' },
            take: 20,
        });
        return rows.map((r) => r.product);
    }

    private async assertExists(id: string) {
        const product = await this.prisma.product.findUnique({ where: { id } });
        if (!product) throw new NotFoundException('Product not found');
        return product;
    }
}
