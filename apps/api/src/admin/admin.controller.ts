import {
    Body,
    Controller,
    Delete,
    Get,
    HttpCode,
    HttpStatus,
    Param,
    Patch,
    Post,
    Query,
    UseGuards,
} from '@nestjs/common';
import { AdminService } from './admin.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { AdminUsersQueryDto } from './dto/admin-users-query.dto';
import { SuspendUserDto } from './dto/suspend-user.dto';
import { CreateAdminDto } from './dto/create-admin.dto';
import { UpdateSettingDto } from './dto/update-setting.dto';
import { RejectProductDto } from './dto/reject-product.dto';
import { RejectPayoutDto } from '../wallet/dto/reject-payout.dto';
import { MarkPaidDto } from '../wallet/dto/mark-paid.dto';
import { ResolveDisputeDto } from '../disputes/dto/resolve-dispute.dto';

@UseGuards(JwtAuthGuard, RolesGuard)
@Roles('ADMIN', 'SUPER_ADMIN')
@Controller('admin')
export class AdminController {
    constructor(private readonly adminService: AdminService) {}

    // ── Dashboard ──────────────────────────────────────────────────────────────

    @Get('stats')
    getStats() {
        return this.adminService.getStats();
    }

    // ── Users ──────────────────────────────────────────────────────────────────

    @Get('users')
    listUsers(@Query() query: AdminUsersQueryDto) {
        return this.adminService.listUsers(query);
    }

    @Get('users/:id')
    getUserDetail(@Param('id') id: string) {
        return this.adminService.getUserDetail(id);
    }

    @Patch('users/:id/suspend')
    @HttpCode(HttpStatus.OK)
    suspendUser(@Param('id') id: string, @Body() dto: SuspendUserDto) {
        return this.adminService.suspendUser(id, dto);
    }

    @Patch('users/:id/ban')
    @HttpCode(HttpStatus.OK)
    banUser(@Param('id') id: string, @Body() dto: SuspendUserDto) {
        return this.adminService.banUser(id, dto);
    }

    @Patch('users/:id/activate')
    @HttpCode(HttpStatus.OK)
    activateUser(@Param('id') id: string) {
        return this.adminService.activateUser(id);
    }

    @Patch('users/:id/approve-profile')
    @HttpCode(HttpStatus.OK)
    approveProfile(@Param('id') id: string) {
        return this.adminService.approveUserProfile(id);
    }

    @Roles('SUPER_ADMIN')
    @Post('users/create-admin')
    createAdmin(@Body() dto: CreateAdminDto) {
        return this.adminService.createAdmin(dto);
    }

    @Roles('SUPER_ADMIN')
    @Delete('users/:id/revoke-admin')
    @HttpCode(HttpStatus.OK)
    revokeAdmin(@Param('id') id: string) {
        return this.adminService.revokeAdmin(id);
    }

    // ── Products ───────────────────────────────────────────────────────────────

    @Get('products')
    listProducts(
        @Query('approvalStatus') approvalStatus?: string,
        @Query('page') page?: string,
        @Query('limit') limit?: string,
    ) {
        return this.adminService.listProducts(
            approvalStatus,
            page ? parseInt(page, 10) : 1,
            limit ? parseInt(limit, 10) : 20,
        );
    }

    @Patch('products/:id/approve')
    @HttpCode(HttpStatus.OK)
    approveProduct(@Param('id') id: string) {
        return this.adminService.approveProduct(id);
    }

    @Patch('products/:id/reject')
    @HttpCode(HttpStatus.OK)
    rejectProduct(@Param('id') id: string, @Body() dto: RejectProductDto) {
        return this.adminService.rejectProduct(id, dto.reason);
    }

    // ── Disputes ───────────────────────────────────────────────────────────────

    @Get('disputes')
    listDisputes(@Query('status') status?: string) {
        return this.adminService.listDisputes(status);
    }

    @Patch('disputes/:id/take')
    @HttpCode(HttpStatus.OK)
    takeDispute(@Param('id') id: string) {
        return this.adminService.takeDispute(id);
    }

    @Patch('disputes/:id/resolve')
    @HttpCode(HttpStatus.OK)
    resolveDispute(
        @Param('id') id: string,
        @CurrentUser() user: { id: string },
        @Body() dto: ResolveDisputeDto,
    ) {
        return this.adminService.resolveDispute(id, user.id, dto);
    }

    @Patch('disputes/:id/dismiss')
    @HttpCode(HttpStatus.OK)
    dismissDispute(@Param('id') id: string, @CurrentUser() user: { id: string }) {
        return this.adminService.dismissDispute(id, user.id);
    }

    // ── Payouts ────────────────────────────────────────────────────────────────

    @Get('payouts')
    listPayouts(@Query('status') status?: string) {
        return this.adminService.listPayouts(status);
    }

    @Post('payouts/:id/reject')
    rejectPayout(
        @Param('id') id: string,
        @CurrentUser() user: { id: string },
        @Body() dto: RejectPayoutDto,
    ) {
        return this.adminService.rejectPayout(id, user.id, dto);
    }

    @Post('payouts/:id/mark-paid')
    markPayoutPaid(
        @Param('id') id: string,
        @CurrentUser() user: { id: string },
        @Body() dto: MarkPaidDto,
    ) {
        return this.adminService.markPayoutPaid(id, user.id, dto);
    }

    // ── Platform Settings ──────────────────────────────────────────────────────

    @Get('settings')
    listSettings() {
        return this.adminService.listSettings();
    }

    @Roles('SUPER_ADMIN')
    @Patch('settings/:key')
    @HttpCode(HttpStatus.OK)
    updateSetting(@Param('key') key: string, @Body() dto: UpdateSettingDto) {
        return this.adminService.updateSetting(key, dto);
    }
}
