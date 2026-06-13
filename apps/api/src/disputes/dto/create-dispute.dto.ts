import {
    IsArray,
    IsEnum,
    IsNumber,
    IsOptional,
    IsString,
    MinLength,
    IsUrl,
    Min,
} from 'class-validator';

export class CreateDisputeDto {
    @IsString()
    respondentId: string;

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

    @IsString()
    @MinLength(5)
    subject: string;

    @IsString()
    @MinLength(20)
    description: string;

    @IsOptional()
    @IsArray()
    @IsUrl({}, { each: true })
    evidencePhotos?: string[];

    @IsOptional()
    @IsNumber()
    @Min(0)
    rentalClaimAmount?: number;
}
