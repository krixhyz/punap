import { Controller, Get, Param, Post, Query, UseGuards } from '@nestjs/common';
import { WishlistService } from './wishlist.service';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';

@UseGuards(JwtAuthGuard)
@Controller('wishlist')
export class WishlistController {
    constructor(private readonly wishlistService: WishlistService) {}

    @Post(':productId')
    toggle(
        @CurrentUser() user: { id: string },
        @Param('productId') productId: string,
    ) {
        return this.wishlistService.toggle(user.id, productId);
    }

    @Get()
    findMy(
        @CurrentUser() user: { id: string },
        @Query('page') page?: string,
        @Query('limit') limit?: string,
    ) {
        return this.wishlistService.findMy(
            user.id,
            page ? parseInt(page, 10) : 1,
            limit ? parseInt(limit, 10) : 20,
        );
    }
}
