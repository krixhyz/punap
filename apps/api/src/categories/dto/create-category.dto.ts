import { IsNotEmpty, IsNumber, IsOptional, IsString, Min } from 'class-validator';

export class CreateCategoryDto {
    @IsString()
    @IsNotEmpty()
    name: string;

    @IsString()
    @IsOptional()
    icon?: string;

    @IsNumber()
    @Min(0)
    @IsOptional()
    ecoPoints?: number;

    @IsNumber()
    @IsOptional()
    parentId?: number;
}
