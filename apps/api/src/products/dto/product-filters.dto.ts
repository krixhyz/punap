import { IsEnum, IsIn, IsInt, IsNumber, IsOptional, IsString, Min } from 'class-validator';
import { Transform, Type } from 'class-transformer';

export class ProductFiltersDto {
    @Type(() => Number)
    @IsInt()
    @IsOptional()
    categoryId?: number;

    @IsIn(['BUY', 'RENT', 'SWAP'])
    @IsOptional()
    transactionType?: string;

    @IsEnum(['NEW', 'LIKE_NEW', 'GOOD', 'FAIR', 'POOR'])
    @IsOptional()
    condition?: string;

    @Type(() => Number)
    @IsNumber()
    @IsOptional()
    minPrice?: number;

    @Type(() => Number)
    @IsNumber()
    @IsOptional()
    maxPrice?: number;

    @Type(() => Number)
    @IsInt()
    @IsOptional()
    provinceId?: number;

    @Type(() => Number)
    @IsInt()
    @IsOptional()
    cityId?: number;

    @IsString()
    @IsOptional()
    keyword?: string;

    @IsString()
    @IsOptional()
    sellerId?: string;

    @Type(() => Number)
    @IsInt()
    @Min(1)
    @IsOptional()
    page?: number;

    @Type(() => Number)
    @IsInt()
    @Min(1)
    @IsOptional()
    limit?: number;
}
