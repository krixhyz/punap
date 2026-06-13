import { Injectable, Logger, UnsupportedMediaTypeException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as path from 'path';
import * as fs from 'fs/promises';
import { v4 as uuidv4 } from 'uuid';
import { S3Client, DeleteObjectCommand } from '@aws-sdk/client-s3';
import { Upload } from '@aws-sdk/lib-storage';

const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

@Injectable()
export class StorageService {
    private readonly logger = new Logger(StorageService.name);
    private readonly driver: 'local' | 's3';
    private readonly s3?: S3Client;
    private readonly bucket?: string;
    private readonly cdnBase?: string;
    private readonly uploadsDir: string;

    constructor(private readonly config: ConfigService) {
        this.driver = (config.get<string>('STORAGE_DRIVER', 'local') as 'local' | 's3');
        this.uploadsDir = path.join(process.cwd(), 'public', 'uploads');

        if (this.driver === 's3') {
            this.s3 = new S3Client({
                region: config.getOrThrow('AWS_REGION'),
                credentials: {
                    accessKeyId: config.getOrThrow('AWS_ACCESS_KEY_ID'),
                    secretAccessKey: config.getOrThrow('AWS_SECRET_ACCESS_KEY'),
                },
            });
            this.bucket = config.getOrThrow('AWS_S3_BUCKET');
            this.cdnBase = config.get('CDN_BASE_URL', `https://${this.bucket}.s3.amazonaws.com`);
        }
    }

    async upload(file: Express.Multer.File): Promise<string> {
        this.validateMime(file.mimetype);
        const ext = path.extname(file.originalname) || this.mimeToExt(file.mimetype);
        const key = `${uuidv4()}${ext}`;

        if (this.driver === 's3') {
            return this.uploadToS3(key, file);
        }
        return this.uploadToLocal(key, file);
    }

    async uploadMany(files: Express.Multer.File[]): Promise<string[]> {
        return Promise.all(files.map((f) => this.upload(f)));
    }

    async delete(urlOrKey: string): Promise<void> {
        if (this.driver === 's3') {
            const key = urlOrKey.replace(`${this.cdnBase}/`, '');
            await this.s3!.send(new DeleteObjectCommand({ Bucket: this.bucket!, Key: key }));
        } else {
            const filename = path.basename(urlOrKey);
            const filePath = path.join(this.uploadsDir, filename);
            await fs.unlink(filePath).catch(() => {
                this.logger.warn(`Could not delete file: ${filePath}`);
            });
        }
    }

    private async uploadToLocal(key: string, file: Express.Multer.File): Promise<string> {
        await fs.mkdir(this.uploadsDir, { recursive: true });
        const filePath = path.join(this.uploadsDir, key);
        await fs.writeFile(filePath, file.buffer);
        return `/uploads/${key}`;
    }

    private async uploadToS3(key: string, file: Express.Multer.File): Promise<string> {
        const upload = new Upload({
            client: this.s3!,
            params: {
                Bucket: this.bucket!,
                Key: key,
                Body: file.buffer,
                ContentType: file.mimetype,
            },
        });
        await upload.done();
        return `${this.cdnBase}/${key}`;
    }

    private validateMime(mimetype: string): void {
        if (!ALLOWED_MIMES.includes(mimetype)) {
            throw new UnsupportedMediaTypeException(`File type ${mimetype} not allowed`);
        }
    }

    private mimeToExt(mime: string): string {
        const map: Record<string, string> = {
            'image/jpeg': '.jpg',
            'image/png': '.png',
            'image/webp': '.webp',
            'image/gif': '.gif',
        };
        return map[mime] ?? '.jpg';
    }
}
