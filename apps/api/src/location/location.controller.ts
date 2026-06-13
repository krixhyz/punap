import { Controller, Get, Param } from '@nestjs/common';
import { LocationService } from './location.service';

@Controller('location')
export class LocationController {
    constructor(private readonly locationService: LocationService) {}

    @Get('provinces')
    getProvinces() {
        return this.locationService.getProvinces();
    }

    @Get('cities/:provinceId')
    getCities(@Param('provinceId') provinceId: string) {
        return this.locationService.getCities(provinceId);
    }
}
