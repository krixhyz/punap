import { IsNumber, IsOptional, IsString, Min, MinLength } from 'class-validator';

export class ResolveDisputeDto {
    @IsString()
    @MinLength(10)
    resolution: string;

    @IsOptional()
    @IsString()
    favoredUserId?: string;

    @IsOptional()
    @IsNumber()
    @Min(0)
    rentalClaimAmount?: number;
}
