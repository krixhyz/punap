import { Module } from '@nestjs/common';
import { PrismaModule } from '../prisma/prisma.module';
import { EcoScoreService } from './eco-score.service';
import { EcoScoreController } from './eco-score.controller';

@Module({
    imports: [PrismaModule],
    providers: [EcoScoreService],
    controllers: [EcoScoreController],
    exports: [EcoScoreService],
})
export class EcoScoreModule {}
