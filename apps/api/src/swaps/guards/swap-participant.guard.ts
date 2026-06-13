import {
    CanActivate,
    ExecutionContext,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../../prisma/prisma.service';

@Injectable()
export class SwapParticipantGuard implements CanActivate {
    constructor(private readonly prisma: PrismaService) {}

    async canActivate(context: ExecutionContext): Promise<boolean> {
        const req = context.switchToHttp().getRequest();
        const userId = req.user?.id;
        const swapId = req.params?.id;

        const swap = await this.prisma.swapRequest.findUnique({
            where: { id: swapId },
            select: { ownerId: true, requesterId: true },
        });
        if (!swap) throw new NotFoundException('Swap request not found');
        if (swap.ownerId !== userId && swap.requesterId !== userId) {
            throw new ForbiddenException('Not a participant in this swap');
        }
        return true;
    }
}
