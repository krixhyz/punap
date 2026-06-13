import { Type } from 'class-transformer';
import { IsIn, IsInt, IsOptional, IsString, Max, Min } from 'class-validator';

export class SearchQueryDto {
    @IsOptional()
    @IsString()
    q?: string;

    @IsOptional()
    @IsString()
    categoryId?: string;

    @IsOptional()
    @IsString()
    transactionType?: string;

    @IsOptional()
    @IsString()
    condition?: string;

    @IsOptional()
    @Type(() => Number)
    minPrice?: number;

    @IsOptional()
    @Type(() => Number)
    maxPrice?: number;

    @IsOptional()
    @IsString()
    provinceId?: string;

    @IsOptional()
    @IsString()
    cityId?: string;

    @IsOptional()
    @IsIn(['price_asc', 'price_desc', 'newest', 'eco_score'])
    sortBy?: 'price_asc' | 'price_desc' | 'newest' | 'eco_score';

    @IsOptional()
    @Type(() => Number)
    @IsInt()
    @Min(1)
    page?: number;

    @IsOptional()
    @Type(() => Number)
    @IsInt()
    @Min(1)
    @Max(100)
    limit?: number;
}
