import {
    Body,
    Controller,
    Get,
    Param,
    Post,
    Query,
    Req,
    Res,
    UseGuards,
} from '@nestjs/common';
import { Response } from 'express';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { PaymentsService } from './payments.service';
import { InitiatePaymentDto } from './dto/initiate-payment.dto';
import { ConfigService } from '@nestjs/config';

@Controller('payments')
export class PaymentsController {
    constructor(
        private readonly paymentsService: PaymentsService,
        private readonly config: ConfigService,
    ) {}

    /** POST /payments/initiate/order/:orderId */
    @UseGuards(JwtAuthGuard)
    @Post('initiate/order/:orderId')
    initiateOrder(
        @Param('orderId') orderId: string,
        @Req() req: any,
        @Query() dto: InitiatePaymentDto,
    ) {
        return this.paymentsService.initiateForOrder(orderId, req.user.id, dto.gateway ?? 'khalti');
    }

    /** POST /payments/initiate/rental/:rentalBookingId */
    @UseGuards(JwtAuthGuard)
    @Post('initiate/rental/:rentalBookingId')
    initiateRental(
        @Param('rentalBookingId') rentalBookingId: string,
        @Req() req: any,
        @Query() dto: InitiatePaymentDto,
    ) {
        return this.paymentsService.initiateForRental(rentalBookingId, req.user.id, dto.gateway ?? 'khalti');
    }

    /** POST /payments/initiate/swap/:swapRequestId */
    @UseGuards(JwtAuthGuard)
    @Post('initiate/swap/:swapRequestId')
    initiateSwap(
        @Param('swapRequestId') swapRequestId: string,
        @Req() req: any,
        @Query() dto: InitiatePaymentDto,
    ) {
        return this.paymentsService.initiateForSwap(swapRequestId, req.user.id, dto.gateway ?? 'khalti');
    }

    /** GET /payments/callback/khalti — public route (no JWT) */
    @Get('callback/khalti')
    async khaltiCallback(@Query('pidx') pidx: string, @Res() res: Response) {
        const frontendUrl = this.config.get<string>('FRONTEND_URL', 'http://localhost:5173');
        if (!pidx) return res.redirect(`${frontendUrl}/payment/failure`);
        const result = await this.paymentsService.handleKhaltiCallback(pidx);
        return res.redirect(
            result.success
                ? `${frontendUrl}/payment/success?pidx=${pidx}`
                : `${frontendUrl}/payment/failure`,
        );
    }

    /** POST /payments/callback/esewa — public route (no JWT) */
    @Post('callback/esewa')
    async esewaCallback(@Body() payload: Record<string, string>, @Res() res: Response) {
        const frontendUrl = this.config.get<string>('FRONTEND_URL', 'http://localhost:5173');
        const result = await this.paymentsService.handleEsewaCallback(payload);
        return res.redirect(
            result.success
                ? `${frontendUrl}/payment/success`
                : `${frontendUrl}/payment/failure`,
        );
    }

    /** GET /payments/:id */
    @UseGuards(JwtAuthGuard)
    @Get(':id')
    findOne(@Param('id') id: string, @Req() req: any) {
        return this.paymentsService.findById(id, req.user.id);
    }
}
