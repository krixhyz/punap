import {
    CanActivate,
    ExecutionContext,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../../prisma/prisma.service';

@Injectable()
export class OrderOwnerGuard implements CanActivate {
    constructor(private readonly prisma: PrismaService) {}

    async canActivate(context: ExecutionContext): Promise<boolean> {
        const request = context.switchToHttp().getRequest();
        const user = request.user as { id: string };
        const orderId = request.params.id as string;

        const order = await this.prisma.order.findUnique({
            where: { id: orderId },
            include: { product: { select: { sellerId: true } } },
        });
        if (!order) throw new NotFoundException('Order not found');

        const isBuyer = order.buyerId === user.id;
        const isSeller = order.product.sellerId === user.id;
        if (!isBuyer && !isSeller) throw new ForbiddenException('Not a participant in this order');
        return true;
    }
}
