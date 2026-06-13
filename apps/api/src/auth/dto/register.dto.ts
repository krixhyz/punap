import { IsEmail, IsNotEmpty, IsOptional, IsString, MinLength, IsInt, IsPositive } from 'class-validator';

export class RegisterDto {
    @IsString()
    @IsNotEmpty()
    name: string;

    @IsEmail()
    email: string;

    @IsString()
    @MinLength(8)
    password: string;

    @IsString()
    @IsOptional()
    phone?: string;

    @IsString()
    @IsOptional()
    address?: string;

    @IsInt()
    @IsPositive()
    @IsOptional()
    provinceId?: number;

    @IsInt()
    @IsPositive()
    @IsOptional()
    cityId?: number;
}
