import {
    Body,
    Controller,
    ForbiddenException,
    Get,
    Param,
    Post,
    Query,
    UseGuards,
} from '@nestjs/common';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { DisputesService } from './disputes.service';
import { CreateDisputeDto } from './dto/create-dispute.dto';
import { ResolveDisputeDto } from './dto/resolve-dispute.dto';

@UseGuards(JwtAuthGuard)
@Controller('disputes')
export class DisputesController {
    constructor(private readonly disputes: DisputesService) {}

    @Post()
    create(@CurrentUser() user: { id: string }, @Body() dto: CreateDisputeDto) {
        return this.disputes.create(user.id, dto);
    }

    @Get()
    findMine(@CurrentUser() user: { id: string }) {
        return this.disputes.findMyDisputes(user.id);
    }

    @Get('admin')
    adminFindAll(@CurrentUser() user: { role: string }, @Query('status') status?: string) {
        this.requireAdmin(user);
        return this.disputes.adminFindAll(status);
    }

    @Get(':id')
    findById(@CurrentUser() user: { id: string }, @Param('id') id: string) {
        return this.disputes.findById(id, user.id);
    }

    @Post(':id/in-review')
    markInReview(@CurrentUser() user: { role: string }, @Param('id') id: string) {
        this.requireAdmin(user);
        return this.disputes.adminMarkInReview(id);
    }

    @Post(':id/resolve')
    resolve(
        @CurrentUser() user: { id: string; role: string },
        @Param('id') id: string,
        @Body() dto: ResolveDisputeDto,
    ) {
        this.requireAdmin(user);
        return this.disputes.adminResolve(id, user.id, dto);
    }

    @Post(':id/dismiss')
    dismiss(@CurrentUser() user: { id: string; role: string }, @Param('id') id: string) {
        this.requireAdmin(user);
        return this.disputes.adminDismiss(id, user.id);
    }

    private requireAdmin(user: { role: string }): void {
        if (user.role !== 'ADMIN') throw new ForbiddenException('Admin only');
    }
}
