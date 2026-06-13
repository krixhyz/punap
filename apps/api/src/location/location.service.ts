import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';

@Injectable()
export class LocationService {
    constructor(private readonly prisma: PrismaService) {}

    getProvinces() {
        return this.prisma.province.findMany({ orderBy: { name: 'asc' } });
    }

    getCities(provinceId: string) {
        return this.prisma.city.findMany({
            where: { provinceId: parseInt(provinceId, 10) },
            orderBy: { name: 'asc' },
        });
    }
}
