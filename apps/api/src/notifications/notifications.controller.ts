import { Controller, Get, Param, Post, Query, UseGuards } from '@nestjs/common';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { NotificationsService } from './notifications.service';
import { NotificationsQueryDto } from './dto/notifications-query.dto';

@UseGuards(JwtAuthGuard)
@Controller('notifications')
export class NotificationsController {
    constructor(private readonly notifications: NotificationsService) {}

    @Get()
    findMine(@CurrentUser() user: { id: string }, @Query() query: NotificationsQueryDto) {
        return this.notifications.findMine(user.id, query);
    }

    @Post(':id/read')
    markRead(@CurrentUser() user: { id: string }, @Param('id') id: string) {
        return this.notifications.markRead(id, user.id);
    }

    @Post('read-all')
    markAllRead(@CurrentUser() user: { id: string }) {
        return this.notifications.markAllRead(user.id);
    }
}
