import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { CreateCategoryDto } from './dto/create-category.dto';
import { UpdateCategoryDto } from './dto/update-category.dto';

@Injectable()
export class CategoriesService {
    constructor(private readonly prisma: PrismaService) {}

    async findAll() {
        return this.prisma.category.findMany({
            include: { children: true },
            where: { parentId: null },
            orderBy: { name: 'asc' },
        });
    }

    async findById(id: number) {
        const category = await this.prisma.category.findUnique({
            where: { id },
            include: { children: true, parent: true },
        });
        if (!category) throw new NotFoundException('Category not found');
        return category;
    }

    async findSubcategories(parentId: number) {
        const parent = await this.prisma.category.findUnique({ where: { id: parentId } });
        if (!parent) throw new NotFoundException('Parent category not found');
        return this.prisma.category.findMany({
            where: { parentId },
            orderBy: { name: 'asc' },
        });
    }

    async create(dto: CreateCategoryDto) {
        if (dto.parentId) {
            const parent = await this.prisma.category.findUnique({ where: { id: dto.parentId } });
            if (!parent) throw new NotFoundException('Parent category not found');
        }
        return this.prisma.category.create({
            data: {
                name: dto.name,
                icon: dto.icon,
                ecoPoints: dto.ecoPoints ?? 0,
                parentId: dto.parentId,
            },
        });
    }

    async update(id: number, dto: UpdateCategoryDto) {
        await this.findById(id);
        return this.prisma.category.update({
            where: { id },
            data: dto,
        });
    }

    async remove(id: number) {
        await this.findById(id);
        const productCount = await this.prisma.product.count({ where: { categoryId: id } });
        if (productCount > 0) {
            throw new BadRequestException('Cannot delete category with linked products');
        }
        return this.prisma.category.delete({ where: { id } });
    }
}
