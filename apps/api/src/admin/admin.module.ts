import { Module } from '@nestjs/common';
import { AdminController } from './admin.controller';
import { AdminService } from './admin.service';
import { ProductsModule } from '../products/products.module';
import { DisputesModule } from '../disputes/disputes.module';
import { WalletModule } from '../wallet/wallet.module';
import { NotificationsModule } from '../notifications/notifications.module';

@Module({
    imports: [ProductsModule, DisputesModule, WalletModule, NotificationsModule],
    controllers: [AdminController],
    providers: [AdminService],
})
export class AdminModule {}
