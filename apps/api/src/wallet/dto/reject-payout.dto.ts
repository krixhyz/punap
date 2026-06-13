import { IsString, MinLength } from 'class-validator';

export class RejectPayoutDto {
    @IsString()
    @MinLength(5)
    reason: string;
}
