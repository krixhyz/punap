import { IsIn, IsNumber, IsOptional, IsString, IsUUID, Min } from 'class-validator';
import { Type } from 'class-transformer';

export class CreateSwapDto {
    @IsUUID()
    productId: string;

    @IsUUID()
    offeredProductId: string;

    @IsOptional()
    @IsString()
    message?: string;

    @IsOptional()
    @Type(() => Number)
    @IsNumber()
    @Min(0)
    offeredAmount?: number = 0;

    @IsOptional()
    @Type(() => Number)
    @IsNumber()
    @Min(0)
    askedAmount?: number = 0;

    @IsOptional()
    @IsIn(['NONE', 'OWNER_ASKS_CASH', 'REQUESTER_OFFERS_CASH'])
    moneyDirection?: 'NONE' | 'OWNER_ASKS_CASH' | 'REQUESTER_OFFERS_CASH' = 'NONE';
}
