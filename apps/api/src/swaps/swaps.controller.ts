import {
    Body,
    Controller,
    Get,
    Param,
    Post,
    Query,
    Req,
    UseGuards,
} from '@nestjs/common';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { SwapsService } from './swaps.service';
import { SwapParticipantGuard } from './guards/swap-participant.guard';
import { CreateSwapDto } from './dto/create-swap.dto';
import { CounterOfferDto } from './dto/counter-offer.dto';
import { SwapsQueryDto } from './dto/swaps-query.dto';

@UseGuards(JwtAuthGuard)
@Controller('swaps')
export class SwapsController {
    constructor(private readonly swapsService: SwapsService) {}

    /** POST /swaps — propose a swap */
    @Post()
    propose(@Req() req: any, @Body() dto: CreateSwapDto) {
        return this.swapsService.propose(req.user.id, dto);
    }

    /** GET /swaps */
    @Get()
    findAll(@Req() req: any, @Query() query: SwapsQueryDto) {
        return this.swapsService.findAll(req.user.id, query);
    }

    /** GET /swaps/:id */
    @Get(':id')
    @UseGuards(SwapParticipantGuard)
    findOne(@Param('id') id: string, @Req() req: any) {
        return this.swapsService.findById(id, req.user.id);
    }

    /** GET /swaps/:id/events */
    @Get(':id/events')
    @UseGuards(SwapParticipantGuard)
    events(@Param('id') id: string, @Req() req: any) {
        return this.swapsService.getEvents(id, req.user.id);
    }

    /** POST /swaps/:id/counter */
    @Post(':id/counter')
    counter(@Param('id') id: string, @Req() req: any, @Body() dto: CounterOfferDto) {
        return this.swapsService.counter(id, req.user.id, dto);
    }

    /** POST /swaps/:id/accept */
    @Post(':id/accept')
    accept(@Param('id') id: string, @Req() req: any) {
        return this.swapsService.accept(id, req.user.id);
    }

    /** POST /swaps/:id/reject */
    @Post(':id/reject')
    reject(@Param('id') id: string, @Req() req: any) {
        return this.swapsService.reject(id, req.user.id);
    }

    /** POST /swaps/:id/cancel */
    @Post(':id/cancel')
    cancel(@Param('id') id: string, @Req() req: any) {
        return this.swapsService.cancel(id, req.user.id);
    }

    /** POST /swaps/:id/confirm-received */
    @Post(':id/confirm-received')
    @UseGuards(SwapParticipantGuard)
    confirmReceived(@Param('id') id: string, @Req() req: any) {
        return this.swapsService.confirmReceived(id, req.user.id);
    }
}
