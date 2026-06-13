import { Test, TestingModule } from '@nestjs/testing';
import { ConflictException, UnauthorizedException, BadRequestException } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import { ConfigService } from '@nestjs/config';
import * as bcrypt from 'bcrypt';
import * as crypto from 'crypto';
import { AuthService } from './auth.service';
import { MailService } from './mail/mail.service';
import { PrismaService } from '../prisma/prisma.service';

const mockUser = {
    id: 'user-1',
    email: 'test@example.com',
    password: '',
    role: 'USER',
    accountStatus: 'ACTIVE',
    name: 'Test User',
    phone: null,
    address: null,
    provinceId: null,
    cityId: null,
    avatarUrl: null,
    emailVerifiedAt: null,
    totalEcoScore: '0',
    ecoLevel: 'NONE',
    profileStatus: 'UNVERIFIED',
    statusNotes: null,
    termsAcceptedAt: null,
    createdAt: new Date(),
    updatedAt: new Date(),
};

const mockPrisma = {
    user: {
        findUnique: jest.fn(),
        create: jest.fn(),
        update: jest.fn(),
    },
    refreshToken: {
        create: jest.fn(),
        findUnique: jest.fn(),
        updateMany: jest.fn(),
    },
    emailVerificationToken: {
        findUnique: jest.fn(),
        create: jest.fn(),
        delete: jest.fn(),
        deleteMany: jest.fn(),
    },
    passwordResetToken: {
        findUnique: jest.fn(),
        create: jest.fn(),
        delete: jest.fn(),
        deleteMany: jest.fn(),
    },
    $transaction: jest.fn((ops: unknown[]) => Promise.all(ops as Promise<unknown>[])),
};

const mockJwt = { sign: jest.fn().mockReturnValue('access-token') };
const mockConfig = { getOrThrow: jest.fn().mockReturnValue('secret'), get: jest.fn().mockReturnValue('7d') };
const mockMail = { sendVerificationEmail: jest.fn(), sendPasswordResetEmail: jest.fn() };

describe('AuthService', () => {
    let service: AuthService;

    beforeEach(async () => {
        jest.clearAllMocks();
        const module: TestingModule = await Test.createTestingModule({
            providers: [
                AuthService,
                { provide: PrismaService, useValue: mockPrisma },
                { provide: JwtService, useValue: mockJwt },
                { provide: ConfigService, useValue: mockConfig },
                { provide: MailService, useValue: mockMail },
            ],
        }).compile();

        service = module.get<AuthService>(AuthService);
    });

    // ── Register ────────────────────────────────────────────────────────────
    describe('register', () => {
        it('throws ConflictException if email already exists', async () => {
            mockPrisma.user.findUnique.mockResolvedValueOnce(mockUser);
            await expect(service.register({ name: 'A', email: 'test@example.com', password: 'pass1234' }))
                .rejects.toBeInstanceOf(ConflictException);
        });

        it('creates user and sends verification email on success', async () => {
            mockPrisma.user.findUnique.mockResolvedValueOnce(null);
            mockPrisma.user.create.mockResolvedValueOnce({ ...mockUser });
            mockPrisma.emailVerificationToken.create.mockResolvedValueOnce({});
            const result = await service.register({ name: 'A', email: 'new@example.com', password: 'pass1234' });
            expect(mockPrisma.user.create).toHaveBeenCalled();
            expect(mockMail.sendVerificationEmail).toHaveBeenCalled();
            expect(result.message).toContain('Registration successful');
        });
    });

    // ── Login ────────────────────────────────────────────────────────────────
    describe('login', () => {
        it('throws UnauthorizedException for wrong password', async () => {
            const hashed = await bcrypt.hash('correctpass', 12);
            mockPrisma.user.findUnique.mockResolvedValueOnce({ ...mockUser, password: hashed });
            await expect(service.login({ email: 'test@example.com', password: 'wrongpass' }))
                .rejects.toBeInstanceOf(UnauthorizedException);
        });

        it('throws UnauthorizedException for suspended account', async () => {
            mockPrisma.user.findUnique.mockResolvedValueOnce({
                ...mockUser,
                accountStatus: 'SUSPENDED',
                password: await bcrypt.hash('pass', 12),
            });
            await expect(service.login({ email: 'test@example.com', password: 'pass' }))
                .rejects.toBeInstanceOf(UnauthorizedException);
        });

        it('returns accessToken and refreshToken on valid credentials', async () => {
            const hashed = await bcrypt.hash('pass1234', 12);
            mockPrisma.user.findUnique.mockResolvedValueOnce({ ...mockUser, password: hashed });
            mockPrisma.refreshToken.create.mockResolvedValueOnce({});
            const result = await service.login({ email: 'test@example.com', password: 'pass1234' });
            expect(result.accessToken).toBe('access-token');
            expect(result.refreshToken).toBeTruthy();
        });
    });

    // ── Refresh ──────────────────────────────────────────────────────────────
    describe('refresh', () => {
        it('throws UnauthorizedException for revoked token', async () => {
            const raw = crypto.randomBytes(48).toString('hex');
            const hash = crypto.createHash('sha256').update(raw).digest('hex');
            mockPrisma.refreshToken.findUnique.mockResolvedValueOnce({
                tokenHash: hash,
                revokedAt: new Date(),
                expiresAt: new Date(Date.now() + 10000),
                userId: 'user-1',
            });
            await expect(service.refresh(raw)).rejects.toBeInstanceOf(UnauthorizedException);
        });

        it('throws UnauthorizedException for expired token', async () => {
            const raw = crypto.randomBytes(48).toString('hex');
            mockPrisma.refreshToken.findUnique.mockResolvedValueOnce({
                tokenHash: 'hash',
                revokedAt: null,
                expiresAt: new Date(Date.now() - 1000),
                userId: 'user-1',
            });
            await expect(service.refresh(raw)).rejects.toBeInstanceOf(UnauthorizedException);
        });
    });
});
