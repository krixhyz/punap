import { IsDateString, IsOptional, IsString, IsUUID } from 'class-validator';

export class BookRentalDto {
    @IsUUID()
    productId: string;

    @IsDateString()
    startDate: string;

    @IsDateString()
    endDate: string;

    @IsOptional()
    @IsString()
    message?: string;
}
