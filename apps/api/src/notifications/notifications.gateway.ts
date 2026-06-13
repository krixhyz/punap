import {
    OnGatewayConnection,
    OnGatewayDisconnect,
    WebSocketGateway,
    WebSocketServer,
} from '@nestjs/websockets';
import { Server, Socket } from 'socket.io';
import { JwtService } from '@nestjs/jwt';
import { Logger } from '@nestjs/common';

@WebSocketGateway({
    cors: {
        origin: process.env.ALLOWED_ORIGINS?.split(',') ?? ['http://localhost:5173'],
        credentials: true,
    },
})
export class NotificationsGateway implements OnGatewayConnection, OnGatewayDisconnect {
    @WebSocketServer()
    server: Server;

    private readonly logger = new Logger(NotificationsGateway.name);

    constructor(private readonly jwtService: JwtService) {}

    async handleConnection(socket: Socket): Promise<void> {
        const token =
            (socket.handshake.auth?.token as string) ??
            (socket.handshake.query?.token as string);

        if (!token) {
            socket.disconnect();
            return;
        }

        try {
            const payload = this.jwtService.verify<{ sub: string }>(token);
            socket.data.userId = payload.sub;
            socket.join(`user:${payload.sub}`);
            this.logger.debug(`Socket connected: user=${payload.sub}`);
        } catch {
            socket.disconnect();
        }
    }

    handleDisconnect(socket: Socket): void {
        this.logger.debug(`Socket disconnected: user=${socket.data?.userId ?? 'unknown'}`);
    }

    emitToUser(userId: string, event: string, data: unknown): void {
        this.server.to(`user:${userId}`).emit(event, data);
    }
}
