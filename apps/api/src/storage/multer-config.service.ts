import { Injectable } from '@nestjs/common';
import { MulterOptionsFactory, MulterModuleOptions } from '@nestjs/platform-express';
import * as multer from 'multer';

@Injectable()
export class MulterConfigService implements MulterOptionsFactory {
    createMulterOptions(): MulterModuleOptions {
        return {
            storage: multer.memoryStorage(),
            limits: {
                fileSize: 5 * 1024 * 1024, // 5 MB
                files: 8,
            },
            fileFilter: (_req, file, callback) => {
                const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (allowed.includes(file.mimetype)) {
                    callback(null, true);
                } else {
                    callback(new Error(`Unsupported file type: ${file.mimetype}`), false);
                }
            },
        };
    }
}
