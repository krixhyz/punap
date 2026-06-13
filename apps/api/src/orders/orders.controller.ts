import {
    Body,
    Controller,
    Get,
    HttpCode,
    HttpStatus,
    Param,
    Patch,
    Post,
    Query,
    UseGuards,
} from '@nestjs/common';
import { OrdersService } from './orders.service';
import { CreateOrderDto } from './dto/create-order.dto';
import { OrdersQueryDto } from './dto/orders-query.dto';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { CurrentUser } from '../auth/decorators/current-user.decorator';

@UseGuards(JwtAuthGuard)
@Controller('orders')
export class OrdersController {
    constructor(private readonly ordersService: OrdersService) {}

    @Post()
    create(@CurrentUser() user: { id: string }, @Body() dto: CreateOrderDto) {
        return this.ordersService.create(user.id, dto);
    }

    @Get()
    findAll(@CurrentUser() user: { id: string }, @Query() query: OrdersQueryDto) {
        return this.ordersService.findByBuyer(user.id, query);
    }

    @Get('selling')
    findSelling(@CurrentUser() user: { id: string }, @Query() query: OrdersQueryDto) {
        return this.ordersService.findBySeller(user.id, query);
    }

    @Get(':id')
    findOne(@Param('id') id: string, @CurrentUser() user: { id: string }) {
        return this.ordersService.findById(id, user.id);
    }

    @Post(':id/cancel')
    @HttpCode(HttpStatus.OK)
    cancel(@Param('id') id: string, @CurrentUser() user: { id: string }) {
        return this.ordersService.cancel(id, user.id);
    }

    @Patch(':id/complete')
    @HttpCode(HttpStatus.OK)
    complete(@Param('id') id: string, @CurrentUser() user: { id: string }) {
        return this.ordersService.complete(id, user.id);
    }
}
