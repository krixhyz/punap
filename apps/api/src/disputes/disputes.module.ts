import { Module } from '@nestjs/common';
import { PrismaModule } from '../prisma/prisma.module';
import { NotificationsModule } from '../notifications/notifications.module';
import { DisputesService } from './disputes.service';
import { DisputesController } from './disputes.controller';

@Module({
    imports: [PrismaModule, NotificationsModule],
    providers: [DisputesService],
    controllers: [DisputesController],
    exports: [DisputesService],
})
export class DisputesModule {}
