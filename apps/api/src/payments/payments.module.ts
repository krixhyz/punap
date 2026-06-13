import { Module } from '@nestjs/common';
import { PaymentsController } from './payments.controller';
import { PaymentsService } from './payments.service';
import { KhaltiService } from './khalti.service';
import { EsewaService } from './esewa.service';
import { PaymentResolutionService } from './payment-resolution.service';
import { OrdersModule } from '../orders/orders.module';
import { RentalsModule } from '../rentals/rentals.module';
import { SwapsModule } from '../swaps/swaps.module';

@Module({
    imports: [OrdersModule, RentalsModule, SwapsModule],
    controllers: [PaymentsController],
    providers: [PaymentsService, KhaltiService, EsewaService, PaymentResolutionService],
    exports: [PaymentsService, KhaltiService, EsewaService],
})
export class PaymentsModule {}
