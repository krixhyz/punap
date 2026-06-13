import { Controller, Get, Param, UseGuards } from '@nestjs/common';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { EcoScoreService } from './eco-score.service';

@Controller('eco-score')
export class EcoScoreController {
    constructor(private readonly ecoScore: EcoScoreService) {}

    @Get('levels')
    getLevels() {
        return this.ecoScore.getLevels();
    }

    @UseGuards(JwtAuthGuard)
    @Get('me')
    getMyScore(@CurrentUser() user: { id: string }) {
        return this.ecoScore.getEcoScore(user.id);
    }

    @Get(':userId')
    getUserScore(@Param('userId') userId: string) {
        return this.ecoScore.getEcoScore(userId);
    }
}
