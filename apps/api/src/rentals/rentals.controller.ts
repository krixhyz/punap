import {
    Body,
    Controller,
    Get,
    Param,
    Post,
    Query,
    Req,
    UploadedFiles,
    UseGuards,
    UseInterceptors,
} from '@nestjs/common';
import { FilesInterceptor } from '@nestjs/platform-express';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';
import { RentalParticipantGuard } from './guards/rental-participant.guard';
import { RentalsService } from './rentals.service';
import { BookRentalDto } from './dto/book-rental.dto';
import { RentalsQueryDto } from './dto/rentals-query.dto';
import { StorageService } from '../storage/storage.service';

@UseGuards(JwtAuthGuard)
@Controller('rentals')
export class RentalsController {
    constructor(
        private readonly rentalsService: RentalsService,
        private readonly storage: StorageService,
    ) {}

    /** POST /rentals/book */
    @Post('book')
    book(@Req() req: any, @Body() dto: BookRentalDto) {
        return this.rentalsService.book(req.user.id, dto);
    }

    /** GET /rentals — renter's bookings */
    @Get()
    myRentals(@Req() req: any, @Query() query: RentalsQueryDto) {
        return this.rentalsService.findByRenter(req.user.id, query);
    }

    /** GET /rentals/lending — owner's bookings */
    @Get('lending')
    lending(@Req() req: any, @Query() query: RentalsQueryDto) {
        return this.rentalsService.findByOwner(req.user.id, query);
    }

    /** GET /rentals/:id */
    @Get(':id')
    @UseGuards(RentalParticipantGuard)
    findOne(@Param('id') id: string, @Req() req: any) {
        return this.rentalsService.findById(id, req.user.id);
    }

    /** POST /rentals/:id/cancel */
    @Post(':id/cancel')
    cancel(@Param('id') id: string, @Req() req: any) {
        return this.rentalsService.cancel(id, req.user.id);
    }

    /** POST /rentals/:id/request-return — multipart evidence photos */
    @Post(':id/request-return')
    @UseInterceptors(FilesInterceptor('evidence', 8))
    async requestReturn(
        @Param('id') id: string,
        @Req() req: any,
        @UploadedFiles() files: Express.Multer.File[],
    ) {
        const evidenceUrls = files?.length ? await this.storage.uploadMany(files) : [];
        return this.rentalsService.requestReturn(id, req.user.id, evidenceUrls);
    }

    /** POST /rentals/:id/confirm-return */
    @Post(':id/confirm-return')
    confirmReturn(@Param('id') id: string, @Req() req: any) {
        return this.rentalsService.confirmReturn(id, req.user.id);
    }
}
