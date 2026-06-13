import { Injectable, Logger } from '@nestjs/common';
import * as nodemailer from 'nodemailer';
import { ConfigService } from '@nestjs/config';

@Injectable()
export class MailService {
    private readonly logger = new Logger(MailService.name);
    private readonly transporter: nodemailer.Transporter;
    private readonly from: string;

    constructor(private readonly config: ConfigService) {
        const smtpHost = config.get<string>('SMTP_HOST');
        if (smtpHost) {
            this.transporter = nodemailer.createTransport({
                host: smtpHost,
                port: config.get<number>('SMTP_PORT', 587),
                auth: {
                    user: config.get<string>('SMTP_USER'),
                    pass: config.get<string>('SMTP_PASS'),
                },
            });
        } else {
            // Console transport for dev — logs email to terminal
            this.transporter = nodemailer.createTransport({ jsonTransport: true });
        }
        this.from = config.get<string>('MAIL_FROM', 'PUNAP <noreply@punap.com>');
    }

    async sendVerificationEmail(to: string, token: string): Promise<void> {
        const frontendUrl = this.config.get<string>('FRONTEND_URL', 'http://localhost:5173');
        const link = `${frontendUrl}/verify-email?token=${token}`;

        const info = await this.transporter.sendMail({
            from: this.from,
            to,
            subject: 'Verify your PUNAP email',
            html: `<p>Click <a href="${link}">here</a> to verify your email. Link: ${link}</p>`,
        });

        // In dev (jsonTransport), log the mail to terminal
        if ((info as { message?: string }).message) {
            this.logger.log(`[DEV MAIL] Verification email: ${(info as { message: string }).message}`);
        } else {
            this.logger.log(`[DEV MAIL] sendVerificationEmail → to=${to} token=${token} link=${link}`);
        }
    }

    async sendPasswordResetEmail(to: string, token: string): Promise<void> {
        const frontendUrl = this.config.get<string>('FRONTEND_URL', 'http://localhost:5173');
        const link = `${frontendUrl}/reset-password?token=${token}`;

        const info = await this.transporter.sendMail({
            from: this.from,
            to,
            subject: 'Reset your PUNAP password',
            html: `<p>Click <a href="${link}">here</a> to reset your password. Link: ${link}</p>`,
        });

        if ((info as { message?: string }).message) {
            this.logger.log(`[DEV MAIL] Password reset email: ${(info as { message: string }).message}`);
        } else {
            this.logger.log(`[DEV MAIL] sendPasswordResetEmail → to=${to} token=${token} link=${link}`);
        }
    }
}
