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
    UploadedFiles,
    UseGuards,
    UseInterceptors,
} from '@nestjs/common';
import { FilesInterceptor } from '@nestjs/platform-express';
import { ProductsService } from './products.service';
import { CreateProductDto } from './dto/create-product.dto';
import { ProductFiltersDto } from './dto/product-filters.dto';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { OptionalJwtAuthGuard } from '../auth/guards/optional-jwt-auth.guard';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';

@Controller('products')
export class ProductsController {
    constructor(private readonly productsService: ProductsService) {}

    @UseGuards(OptionalJwtAuthGuard)
    @Get()
    findAll(@Query() filters: ProductFiltersDto, @CurrentUser() user?: { id: string }) {
        return this.productsService.findAll(filters);
    }

    @UseGuards(JwtAuthGuard)
    @Get('my')
    findMy(@CurrentUser() user: { id: string }) {
        return this.productsService.findMy(user.id);
    }

    @UseGuards(OptionalJwtAuthGuard)
    @Get(':id')
    findOne(@Param('id') id: string, @CurrentUser() user?: { id: string }) {
        return this.productsService.findById(id, user?.id);
    }

    @UseGuards(JwtAuthGuard)
    @Post()
    @UseInterceptors(FilesInterceptor('images', 8))
    create(
        @CurrentUser() user: { id: string },
        @Body() dto: CreateProductDto,
        @UploadedFiles() files: Express.Multer.File[],
    ) {
        return this.productsService.create(user.id, dto, files ?? []);
    }

    @UseGuards(JwtAuthGuard)
    @Patch(':id')
    @UseInterceptors(FilesInterceptor('images', 8))
    update(
        @Param('id') id: string,
        @CurrentUser() user: { id: string },
        @Body() dto: Partial<CreateProductDto>,
        @UploadedFiles() files: Express.Multer.File[],
    ) {
        return this.productsService.update(id, user.id, dto, files ?? []);
    }

    @UseGuards(JwtAuthGuard)
    @Delete(':id')
    @HttpCode(HttpStatus.OK)
    remove(@Param('id') id: string, @CurrentUser() user: { id: string }) {
        return this.productsService.softDelete(id, user.id);
    }

    @UseGuards(JwtAuthGuard, RolesGuard)
    @Roles('ADMIN', 'SUPER_ADMIN')
    @Patch(':id/approve')
    @HttpCode(HttpStatus.OK)
    approve(@Param('id') id: string) {
        return this.productsService.approve(id);
    }

    @UseGuards(JwtAuthGuard, RolesGuard)
    @Roles('ADMIN', 'SUPER_ADMIN')
    @Patch(':id/reject')
    @HttpCode(HttpStatus.OK)
    reject(@Param('id') id: string, @Body('reason') reason?: string) {
        return this.productsService.reject(id, reason);
    }

    @UseGuards(JwtAuthGuard)
    @Get('recently-viewed')
    getRecentlyViewed(@CurrentUser() user: { id: string }) {
        return this.productsService.getRecentlyViewed(user.id);
    }
}
