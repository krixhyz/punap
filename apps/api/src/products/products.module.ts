import { Module } from '@nestjs/common';
import { ProductsController } from './products.controller';
import { ProductsService } from './products.service';
import { ProductOwnerGuard } from './guards/product-owner.guard';

@Module({
    controllers: [ProductsController],
    providers: [ProductsService, ProductOwnerGuard],
    exports: [ProductsService],
})
export class ProductsModule {}
