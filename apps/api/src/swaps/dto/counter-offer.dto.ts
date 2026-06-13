import { IsIn, IsNumber, IsOptional, IsString, Min } from 'class-validator';
import { Type } from 'class-transformer';

export class CounterOfferDto {
    @IsOptional()
    @Type(() => Number)
    @IsNumber()
    @Min(0)
    offeredAmount?: number;

    @IsOptional()
    @Type(() => Number)
    @IsNumber()
    @Min(0)
    askedAmount?: number;

    @IsOptional()
    @IsIn(['NONE', 'OWNER_ASKS_CASH', 'REQUESTER_OFFERS_CASH'])
    moneyDirection?: 'NONE' | 'OWNER_ASKS_CASH' | 'REQUESTER_OFFERS_CASH';

    @IsOptional()
    @IsString()
    message?: string;
}
