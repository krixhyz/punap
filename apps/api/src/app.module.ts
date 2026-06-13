import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { ServeStaticModule } from '@nestjs/serve-static';
import { ScheduleModule } from '@nestjs/schedule';
import * as path from 'path';
import { PrismaModule } from './prisma/prisma.module';
import { HealthModule } from './health/health.module';
import { AuthModule } from './auth/auth.module';
import { StorageModule } from './storage/storage.module';
import { CategoriesModule } from './categories/categories.module';
import { ProductsModule } from './products/products.module';
import { OrdersModule } from './orders/orders.module';
import { RentalsModule } from './rentals/rentals.module';
import { PaymentsModule } from './payments/payments.module';
import { SwapsModule } from './swaps/swaps.module';
import { WalletModule } from './wallet/wallet.module';
import { ReviewsModule } from './reviews/reviews.module';
import { DisputesModule } from './disputes/disputes.module';
import { EcoScoreModule } from './eco-score/eco-score.module';
import { NotificationsModule } from './notifications/notifications.module';
import { LocationModule } from './location/location.module';
import { SearchModule } from './search/search.module';
import { WishlistModule } from './wishlist/wishlist.module';
import { AdminModule } from './admin/admin.module';

@Module({
    imports: [
        ConfigModule.forRoot({ isGlobal: true }),
        ScheduleModule.forRoot(),
        ServeStaticModule.forRoot({
            rootPath: path.join(process.cwd(), 'public'),
            serveRoot: '/',
        }),
        PrismaModule,
        HealthModule,
        AuthModule,
        StorageModule,
        CategoriesModule,
        ProductsModule,
        OrdersModule,
        RentalsModule,
        PaymentsModule,
        SwapsModule,
        WalletModule,
        ReviewsModule,
        DisputesModule,
        EcoScoreModule,
        NotificationsModule,
        LocationModule,
        SearchModule,
        WishlistModule,
        AdminModule,
    ],
})
export class AppModule {}
