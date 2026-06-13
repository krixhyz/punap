import { Global, Module } from '@nestjs/common';
import { MulterModule } from '@nestjs/platform-express';
import { StorageService } from './storage.service';
import { MulterConfigService } from './multer-config.service';

@Global()
@Module({
    imports: [
        MulterModule.registerAsync({ useClass: MulterConfigService }),
    ],
    providers: [StorageService],
    exports: [StorageService, MulterModule],
})
export class StorageModule {}
