import { Module } from '@nestjs/common';
import { PrismaModule } from '../prisma/prisma.module';
import { NotificationsModule } from '../notifications/notifications.module';
import { WalletLedgerService } from './wallet-ledger.service';
import { WalletController } from './wallet.controller';

@Module({
    imports: [PrismaModule, NotificationsModule],
    providers: [WalletLedgerService],
    controllers: [WalletController],
    exports: [WalletLedgerService],
})
export class WalletModule {}
