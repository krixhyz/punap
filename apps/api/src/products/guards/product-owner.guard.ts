import {
    CanActivate,
    ExecutionContext,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../../prisma/prisma.service';

@Injectable()
export class ProductOwnerGuard implements CanActivate {
    constructor(private readonly prisma: PrismaService) {}

    async canActivate(context: ExecutionContext): Promise<boolean> {
        const request = context.switchToHttp().getRequest();
        const user = request.user as { id: string };
        const productId = request.params.id as string;

        const product = await this.prisma.product.findUnique({
            where: { id: productId },
            select: { sellerId: true },
        });
        if (!product) throw new NotFoundException('Product not found');
        if (product.sellerId !== user.id) throw new ForbiddenException('Not the product owner');
        return true;
    }
}
