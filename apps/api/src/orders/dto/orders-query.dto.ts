import { IsInt, IsOptional, Min } from 'class-validator';
import { Type } from 'class-transformer';

export class OrdersQueryDto {
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
