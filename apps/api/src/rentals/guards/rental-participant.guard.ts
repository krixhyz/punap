import {
    CanActivate,
    ExecutionContext,
    ForbiddenException,
    Injectable,
    NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../../prisma/prisma.service';

@Injectable()
export class RentalParticipantGuard implements CanActivate {
    constructor(private readonly prisma: PrismaService) {}

    async canActivate(context: ExecutionContext): Promise<boolean> {
        const req = context.switchToHttp().getRequest();
        const userId = req.user?.id;
        const rentalId = req.params?.id;

        const rental = await this.prisma.rentalBooking.findUnique({
            where: { id: rentalId },
            select: { renterId: true, ownerId: true },
        });
        if (!rental) throw new NotFoundException('Rental booking not found');
        if (rental.renterId !== userId && rental.ownerId !== userId) {
            throw new ForbiddenException('Not a participant in this rental');
        }
        return true;
    }
}
