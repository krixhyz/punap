import { IsIn, IsOptional } from 'class-validator';

export class InitiatePaymentDto {
    @IsOptional()
    @IsIn(['khalti', 'esewa'])
    gateway?: 'khalti' | 'esewa' = 'khalti';
}
