import {
    BadRequestException,
    ConflictException,
    Injectable,
    UnauthorizedException,
} from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import { ConfigService } from '@nestjs/config';
import * as bcrypt from 'bcrypt';
import * as crypto from 'crypto';
import { PrismaService } from '../prisma/prisma.service';
import { RegisterDto } from './dto/register.dto';
import { LoginDto } from './dto/login.dto';
import { MailService } from './mail/mail.service';

@Injectable()
export class AuthService {
    constructor(
        private readonly prisma: PrismaService,
        private readonly jwt: JwtService,
        private readonly config: ConfigService,
        private readonly mail: MailService,
    ) {}

    // ── Register ──────────────────────────────────────────────────────────────
    async register(dto: RegisterDto) {
        const existing = await this.prisma.user.findUnique({ where: { email: dto.email } });
        if (existing) throw new ConflictException('Email already in use');

        const passwordHash = await bcrypt.hash(dto.password, 12);
        const user = await this.prisma.user.create({
            data: {
                name: dto.name,
                email: dto.email,
                password: passwordHash,
                phone: dto.phone,
                address: dto.address,
                provinceId: dto.provinceId,
                cityId: dto.cityId,
                termsAcceptedAt: new Date(),
            },
        });

        const token = await this.createEmailVerificationToken(user.id);
        await this.mail.sendVerificationEmail(user.email, token);

        return { message: 'Registration successful. Check your email to verify your account.' };
    }

    // ── Login ─────────────────────────────────────────────────────────────────
    async login(dto: LoginDto) {
        const user = await this.prisma.user.findUnique({ where: { email: dto.email } });
        if (!user) throw new UnauthorizedException('Invalid credentials');

        if (user.accountStatus === 'SUSPENDED' || user.accountStatus === 'BANNED') {
            throw new UnauthorizedException(
                `Account is ${user.accountStatus.toLowerCase()}. Contact support.`,
            );
        }

        const valid = await bcrypt.compare(dto.password, user.password);
        if (!valid) throw new UnauthorizedException('Invalid credentials');

        return this.issueTokenPair(user.id, user.email, user.role);
    }

    // ── Refresh ───────────────────────────────────────────────────────────────
    async refresh(rawRefreshToken: string) {
        const tokenHash = this.hashToken(rawRefreshToken);
        const stored = await this.prisma.refreshToken.findUnique({ where: { tokenHash } });

        if (!stored || stored.revokedAt || stored.expiresAt < new Date()) {
            throw new UnauthorizedException('Invalid or expired refresh token');
        }

        const user = await this.prisma.user.findUnique({ where: { id: stored.userId } });
        if (!user) throw new UnauthorizedException('User not found');

        // Rotate: revoke old, issue new
        await this.revokeRefreshToken(tokenHash);
        return this.issueTokenPair(user.id, user.email, user.role);
    }

    // ── Logout ────────────────────────────────────────────────────────────────
    async logout(rawRefreshToken: string): Promise<void> {
        if (!rawRefreshToken) return;
        const tokenHash = this.hashToken(rawRefreshToken);
        await this.revokeRefreshToken(tokenHash);
    }

    // ── Verify Email ──────────────────────────────────────────────────────────
    async verifyEmail(token: string) {
        const tokenHash = this.hashToken(token);
        const stored = await this.prisma.emailVerificationToken.findUnique({ where: { tokenHash } });

        if (!stored || stored.expiresAt < new Date()) {
            throw new BadRequestException('Invalid or expired verification token');
        }

        await this.prisma.$transaction([
            this.prisma.user.update({
                where: { id: stored.userId },
                data: { emailVerifiedAt: new Date() },
            }),
            this.prisma.emailVerificationToken.delete({ where: { id: stored.id } }),
        ]);

        return { message: 'Email verified successfully' };
    }

    // ── Resend Verification ───────────────────────────────────────────────────
    async resendVerification(email: string) {
        const user = await this.prisma.user.findUnique({ where: { email } });
        // Always return 200 to avoid user enumeration
        if (!user || user.emailVerifiedAt) return { message: 'If eligible, a new verification email has been sent.' };

        // Delete any existing tokens for this user
        await this.prisma.emailVerificationToken.deleteMany({ where: { userId: user.id } });

        const token = await this.createEmailVerificationToken(user.id);
        await this.mail.sendVerificationEmail(user.email, token);

        return { message: 'If eligible, a new verification email has been sent.' };
    }

    // ── Forgot Password ───────────────────────────────────────────────────────
    async forgotPassword(email: string) {
        const user = await this.prisma.user.findUnique({ where: { email } });
        // Always return 200 — do not reveal whether email exists
        if (user) {
            await this.prisma.passwordResetToken.deleteMany({ where: { userId: user.id } });
            const token = await this.createPasswordResetToken(user.id);
            await this.mail.sendPasswordResetEmail(user.email, token);
        }
        return { message: 'If an account with that email exists, a password reset link has been sent.' };
    }

    // ── Reset Password ────────────────────────────────────────────────────────
    async resetPassword(token: string, newPassword: string) {
        const tokenHash = this.hashToken(token);
        const stored = await this.prisma.passwordResetToken.findUnique({ where: { tokenHash } });

        if (!stored || stored.expiresAt < new Date()) {
            throw new BadRequestException('Invalid or expired reset token');
        }

        const passwordHash = await bcrypt.hash(newPassword, 12);

        await this.prisma.$transaction([
            this.prisma.user.update({
                where: { id: stored.userId },
                data: { password: passwordHash },
            }),
            this.prisma.passwordResetToken.delete({ where: { id: stored.id } }),
            // Revoke all refresh tokens on password reset
            this.prisma.refreshToken.updateMany({
                where: { userId: stored.userId, revokedAt: null },
                data: { revokedAt: new Date() },
            }),
        ]);

        return { message: 'Password reset successfully. Please log in.' };
    }

    // ── Token Helpers ─────────────────────────────────────────────────────────
    async issueTokenPair(userId: string, email: string, role: string) {
        const accessToken = this.jwt.sign(
            { sub: userId, email, role },
            {
                secret: this.config.getOrThrow('JWT_ACCESS_SECRET'),
                expiresIn: this.config.get('JWT_ACCESS_EXPIRES_IN', '15m'),
            },
        );

        const rawRefresh = crypto.randomBytes(48).toString('hex');
        const tokenHash = this.hashToken(rawRefresh);
        const refreshExpiryDays = parseInt(
            (this.config.get<string>('JWT_REFRESH_EXPIRES_IN', '7d') as string).replace('d', ''),
            10,
        );
        const expiresAt = new Date();
        expiresAt.setDate(expiresAt.getDate() + refreshExpiryDays);

        await this.prisma.refreshToken.create({
            data: { userId, tokenHash, expiresAt },
        });

        return { accessToken, refreshToken: rawRefresh };
    }

    async revokeRefreshToken(tokenHash: string): Promise<void> {
        await this.prisma.refreshToken.updateMany({
            where: { tokenHash, revokedAt: null },
            data: { revokedAt: new Date() },
        });
    }

    async revokeAllRefreshTokens(userId: string): Promise<void> {
        await this.prisma.refreshToken.updateMany({
            where: { userId, revokedAt: null },
            data: { revokedAt: new Date() },
        });
    }

    private hashToken(token: string): string {
        return crypto.createHash('sha256').update(token).digest('hex');
    }

    private async createEmailVerificationToken(userId: string): Promise<string> {
        const raw = crypto.randomBytes(32).toString('hex');
        const tokenHash = this.hashToken(raw);
        const expiresAt = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours

        await this.prisma.emailVerificationToken.create({
            data: { userId, tokenHash, expiresAt },
        });

        return raw;
    }

    private async createPasswordResetToken(userId: string): Promise<string> {
        const raw = crypto.randomBytes(32).toString('hex');
        const tokenHash = this.hashToken(raw);
        const expiresAt = new Date(Date.now() + 60 * 60 * 1000); // 1 hour

        await this.prisma.passwordResetToken.create({
            data: { userId, tokenHash, expiresAt },
        });

        return raw;
    }
}
