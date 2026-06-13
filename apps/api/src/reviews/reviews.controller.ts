import { Body, Controller, Get, Param, Post, Query, UseGuards } from '@nestjs/common';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { ReviewsService } from './reviews.service';
import { CreateReviewDto } from './dto/create-review.dto';
import { ReviewsQueryDto } from './dto/reviews-query.dto';

@Controller('reviews')
export class ReviewsController {
    constructor(private readonly reviews: ReviewsService) {}

    @UseGuards(JwtAuthGuard)
    @Post()
    create(@CurrentUser() user: { id: string }, @Body() dto: CreateReviewDto) {
        return this.reviews.create(user.id, dto);
    }

    @Get()
    findAll(@Query() query: ReviewsQueryDto) {
        return this.reviews.findAll(query);
    }

    @Get(':id')
    findById(@Param('id') id: string) {
        return this.reviews.findById(id);
    }
}
