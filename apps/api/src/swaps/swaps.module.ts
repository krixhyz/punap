import { Module } from '@nestjs/common';
import { SwapsController } from './swaps.controller';
import { SwapsService } from './swaps.service';
import { SwapOrderService } from './swap-order.service';
import { SwapParticipantGuard } from './guards/swap-participant.guard';
import { WalletModule } from '../wallet/wallet.module';
import { EcoScoreModule } from '../eco-score/eco-score.module';

@Module({
    imports: [WalletModule, EcoScoreModule],
    controllers: [SwapsController],
    providers: [SwapsService, SwapOrderService, SwapParticipantGuard],
    exports: [SwapsService, SwapOrderService],
})
export class SwapsModule {}
