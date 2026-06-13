import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { Prisma } from '../generated/prisma/client';

const BASE_ECO_POINTS = 100;

const CONDITION_MULTIPLIER: Record<string, number> = {
    NEW: 1.0,
    LIKE_NEW: 0.95,
    GOOD: 0.85,
    FAIR: 0.70,
    POOR: 0.50,
};

const TRANSACTION_MULTIPLIER: Record<string, number> = {
    SWAP: 1.2,
    BUY: 1.0,
    RENT: 0.6,
};

const ECO_LEVELS = [
    { level: 'PLATINUM', threshold: 1000 },
    { level: 'GOLD', threshold: 500 },
    { level: 'SILVER', threshold: 200 },
    { level: 'BRONZE', threshold: 50 },
    { level: 'NONE', threshold: 0 },
] as const;

function calcEcoLevel(totalPoints: number): string {
    for (const { level, threshold } of ECO_LEVELS) {
        if (totalPoints >= threshold) return level;
    }
    return 'NONE';
}

@Injectable()
export class EcoScoreService {
    constructor(private readonly prisma: PrismaService) {}

    calculateEcoPoints(condition: string, transactionType: string): number {
        const condMult = CONDITION_MULTIPLIER[condition] ?? 1.0;
        const txMult = TRANSACTION_MULTIPLIER[transactionType] ?? 1.0;
        return Math.round(BASE_ECO_POINTS * condMult * txMult);
    }

    async recordEcoImpact(opts: {
        userId: string;
        condition: string;
        transactionType: string;
        transactionId: string;
        productId?: string;
    }): Promise<void> {
        const ecoPointsAwarded = this.calculateEcoPoints(opts.condition, opts.transactionType);
        const ecoLevelAtAward = calcEcoLevel(ecoPointsAwarded);

        try {
            await this.prisma.$transaction(async (tx) => {
                await tx.userEcoScore.create({
                    data: {
                        userId: opts.userId,
                        transactionType: opts.transactionType,
                        productId: opts.productId,
                        transactionId: opts.transactionId,
                        ecoPointsAwarded,
                        ecoLevel: ecoLevelAtAward,
                    },
                });

                const aggregate = await tx.userEcoScore.aggregate({
                    where: { userId: opts.userId },
                    _sum: { ecoPointsAwarded: true },
                });

                const totalPoints = parseFloat(
                    (aggregate._sum.ecoPointsAwarded ?? new Prisma.Decimal(0)).toString(),
                );
                const newLevel = calcEcoLevel(totalPoints);

                await tx.user.update({
                    where: { id: opts.userId },
                    data: { totalEcoScore: totalPoints, ecoLevel: newLevel },
                });
            });
        } catch (e) {
            // P2002 = unique constraint: already recorded for this transaction
            if ((e as Prisma.PrismaClientKnownRequestError).code === 'P2002') return;
            throw e;
        }
    }

    async getEcoScore(userId: string) {
        const [user, records] = await Promise.all([
            this.prisma.user.findUnique({
                where: { id: userId },
                select: { id: true, name: true, totalEcoScore: true, ecoLevel: true },
            }),
            this.prisma.userEcoScore.findMany({
                where: { userId },
                orderBy: { createdAt: 'desc' },
            }),
        ]);
        return { user, records };
    }

    getLevels() {
        return ECO_LEVELS.map(({ level, threshold }) => ({ level, minPoints: threshold }));
    }
}
