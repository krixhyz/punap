import {
    Body,
    Controller,
    Get,
    Param,
    Post,
    Query,
    UseGuards,
    ForbiddenException,
} from '@nestjs/common';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { WalletLedgerService } from './wallet-ledger.service';
import { RequestPayoutDto } from './dto/request-payout.dto';
import { RejectPayoutDto } from './dto/reject-payout.dto';
import { MarkPaidDto } from './dto/mark-paid.dto';
import { LedgerQueryDto } from './dto/ledger-query.dto';

@UseGuards(JwtAuthGuard)
@Controller('wallet')
export class WalletController {
    constructor(private readonly walletService: WalletLedgerService) {}

    @Get()
    getMyWallet(@CurrentUser() user: { id: string }) {
        return this.walletService.getMyWallet(user.id);
    }

    @Get('ledger')
    getLedger(@CurrentUser() user: { id: string }, @Query() query: LedgerQueryDto) {
        return this.walletService.getLedger(user.id, query);
    }

    @Post('payout')
    requestPayout(@CurrentUser() user: { id: string }, @Body() dto: RequestPayoutDto) {
        return this.walletService.requestPayout(user.id, dto);
    }

    @Get('payouts')
    getMyPayouts(@CurrentUser() user: { id: string }) {
        return this.walletService.getMyPayouts(user.id);
    }

    // ── Admin routes ───────────────────────────────────────────────────────────

    @Get('admin/payouts')
    adminListPayouts(@CurrentUser() user: { role: string }, @Query('status') status?: string) {
        this.requireAdmin(user);
        return this.walletService.adminListPayouts(status);
    }

    @Post('admin/payouts/:id/reject')
    rejectPayout(
        @CurrentUser() user: { id: string; role: string },
        @Param('id') id: string,
        @Body() dto: RejectPayoutDto,
    ) {
        this.requireAdmin(user);
        return this.walletService.rejectPayout(id, user.id, dto);
    }

    @Post('admin/payouts/:id/mark-paid')
    markPayoutPaid(
        @CurrentUser() user: { id: string; role: string },
        @Param('id') id: string,
        @Body() dto: MarkPaidDto,
    ) {
        this.requireAdmin(user);
        return this.walletService.markPayoutPaid(id, user.id, dto);
    }

    private requireAdmin(user: { role: string }): void {
        if (user.role !== 'ADMIN') throw new ForbiddenException('Admin only');
    }
}
