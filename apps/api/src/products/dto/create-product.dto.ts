import {
    IsArray,
    IsEnum,
    IsIn,
    IsInt,
    IsNotEmpty,
    IsNumber,
    IsOptional,
    IsPositive,
    IsString,
    Min,
} from 'class-validator';
import { Transform, Type } from 'class-transformer';

export class CreateProductDto {
    @IsString()
    @IsNotEmpty()
    title: string;

    @IsString()
    @IsOptional()
    description?: string;

    @Type(() => Number)
    @IsNumber()
    @IsPositive()
    price: number;

    @Type(() => Number)
    @IsInt()
    @Min(1)
    quantity: number;

    @IsEnum(['NEW', 'LIKE_NEW', 'GOOD', 'FAIR', 'POOR'])
    condition: string;

    @Type(() => Number)
    @IsInt()
    @IsPositive()
    categoryId: number;

    @Transform(({ value }) => {
        if (typeof value === 'string') return value.split(',').map((v: string) => v.trim());
        return value;
    })
    @IsArray()
    @IsIn(['BUY', 'RENT', 'SWAP'], { each: true })
    transactionTypes: string[];

    @Type(() => Number)
    @IsInt()
    @IsOptional()
    provinceId?: number;

    @Type(() => Number)
    @IsInt()
    @IsOptional()
    cityId?: number;

    // Rental config
    @Type(() => Number)
    @IsNumber()
    @IsOptional()
    rentFare?: number;

    @Type(() => Number)
    @IsNumber()
    @IsOptional()
    rentDeposit?: number;

    @IsEnum(['DAILY', 'WEEKLY', 'MONTHLY'])
    @IsOptional()
    rentType?: string;

    @IsString()
    @IsOptional()
    availableFrom?: string;

    @Type(() => Number)
    @IsInt()
    @IsOptional()
    availableDuration?: number;
}
