import { Module } from '@nestjs/common';
import { RentalsController } from './rentals.controller';
import { RentalsService } from './rentals.service';
import { RentalDepositService } from './rental-deposit.service';
import { RentalParticipantGuard } from './guards/rental-participant.guard';
import { OrdersModule } from '../orders/orders.module';
import { WalletModule } from '../wallet/wallet.module';
import { EcoScoreModule } from '../eco-score/eco-score.module';
import { NotificationsModule } from '../notifications/notifications.module';

@Module({
    imports: [OrdersModule, WalletModule, EcoScoreModule, NotificationsModule],
    controllers: [RentalsController],
    providers: [RentalsService, RentalDepositService, RentalParticipantGuard],
    exports: [RentalsService, RentalDepositService],
})
export class RentalsModule {}
