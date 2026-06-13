import { Injectable, OnModuleInit, OnModuleDestroy } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Pool } from 'pg';
import { PrismaPg } from '@prisma/adapter-pg';
import { PrismaClient } from '../generated/prisma/client';

@Injectable()
export class PrismaService extends PrismaClient implements OnModuleInit, OnModuleDestroy {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    private readonly pool!: Pool;

    constructor(private readonly config: ConfigService) {
        const pool = new Pool({
            connectionString: config.getOrThrow<string>('DATABASE_URL'),
        });
        const adapter = new PrismaPg(pool);
        super({ adapter });
        (this as unknown as { pool: Pool }).pool = pool;
    }

    async onModuleInit(): Promise<void> {
        await this.$connect();
    }

    async onModuleDestroy(): Promise<void> {
        await this.$disconnect();
        if (this.pool) await this.pool.end();
    }
}
