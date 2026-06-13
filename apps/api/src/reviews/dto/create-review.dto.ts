import { IsEnum, IsInt, IsOptional, IsString, Max, Min } from 'class-validator';
import { Type } from 'class-transformer';

export class CreateReviewDto {
    @IsString()
    subjectId: string;

    @IsString()
    productId: string;

    @IsEnum(['BUY', 'RENT', 'SWAP'])
    transactionType: string;

    @IsOptional()
    @IsString()
    orderId?: string;

    @IsOptional()
    @IsString()
    rentalBookingId?: string;

    @IsOptional()
    @IsString()
    swapId?: string;

    @Type(() => Number)
    @IsInt()
    @Min(1)
    @Max(5)
    rating: number;

    @IsOptional()
    @IsString()
    body?: string;
}
