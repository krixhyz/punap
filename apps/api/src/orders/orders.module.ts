import { Module } from '@nestjs/common';
import { OrdersController } from './orders.controller';
import { OrdersService } from './orders.service';
import { CheckoutPricingService } from './checkout-pricing.service';
import { InventoryReservationService } from './inventory-reservation.service';
import { OrderOwnerGuard } from './guards/order-owner.guard';
import { WalletModule } from '../wallet/wallet.module';
import { EcoScoreModule } from '../eco-score/eco-score.module';
import { NotificationsModule } from '../notifications/notifications.module';

@Module({
    imports: [WalletModule, EcoScoreModule, NotificationsModule],
    controllers: [OrdersController],
    providers: [OrdersService, CheckoutPricingService, InventoryReservationService, OrderOwnerGuard],
    exports: [OrdersService, CheckoutPricingService],
})
export class OrdersModule {}
