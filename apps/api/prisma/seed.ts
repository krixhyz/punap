import 'dotenv/config';
import * as bcrypt from 'bcrypt';
import { Pool } from 'pg';
import { PrismaPg } from '@prisma/adapter-pg';
import { PrismaClient } from '../src/generated/prisma/client';

const pool = new Pool({ connectionString: process.env.DATABASE_URL });
const adapter = new PrismaPg(pool);
const prisma = new PrismaClient({ adapter });

// ─── Nepal Provinces & Cities ────────────────────────────────────────────────

const PROVINCES: { name: string; cities: string[] }[] = [
    {
        name: 'Koshi Province',
        cities: [
            'Biratnagar', 'Dharan', 'Itahari', 'Birtamod', 'Damak',
            'Inaruwa', 'Rajbiraj', 'Triyuga', 'Dhankuta', 'Ilam',
        ],
    },
    {
        name: 'Madhesh Province',
        cities: [
            'Janakpur', 'Birgunj', 'Rajbiraj', 'Lahan', 'Siraha',
            'Kalaiya', 'Jaleswor', 'Malangwa', 'Gaur', 'Haripur',
        ],
    },
    {
        name: 'Bagmati Province',
        cities: [
            'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Kirtipur', 'Hetauda',
            'Bharatpur', 'Bidur', 'Dhulikhel', 'Banepa', 'Panauti',
            'Thimi', 'Madhyapur Thimi', 'Tansen',
        ],
    },
    {
        name: 'Gandaki Province',
        cities: [
            'Pokhara', 'Baglung', 'Gorkha', 'Damauli', 'Waling',
            'Beni', 'Syangja', 'Kawasoti', 'Ghandruk', 'Lamjung',
        ],
    },
    {
        name: 'Lumbini Province',
        cities: [
            'Butwal', 'Bhairahawa', 'Nepalgunj', 'Tulsipur', 'Tansen',
            'Kapilvastu', 'Gularia', 'Ghorahi', 'Lamahi', 'Kohalpur',
        ],
    },
    {
        name: 'Karnali Province',
        cities: [
            'Birendranagar', 'Jumla', 'Dailekh', 'Surkhet', 'Narayan',
            'Musikot', 'Chhinchu', 'Dunai', 'Rukumkot', 'Manma',
        ],
    },
    {
        name: 'Sudurpashchim Province',
        cities: [
            'Dhangadhi', 'Mahendranagar', 'Tikapur', 'Dipayal Silgadhi',
            'Dadeldhura', 'Baitadi', 'Darchula', 'Chainpur', 'Bajhang', 'Martadi',
        ],
    },
];

// ─── Default Categories ───────────────────────────────────────────────────────

const CATEGORIES: { name: string; ecoPoints: number; icon?: string }[] = [
    { name: 'Electronics',  ecoPoints: 25, icon: 'laptop' },
    { name: 'Clothing',     ecoPoints: 15, icon: 'shirt' },
    { name: 'Furniture',    ecoPoints: 30, icon: 'sofa' },
    { name: 'Books',        ecoPoints: 10, icon: 'book' },
    { name: 'Sports',       ecoPoints: 20, icon: 'dumbbell' },
    { name: 'Vehicles',     ecoPoints: 40, icon: 'car' },
    { name: 'Tools',        ecoPoints: 20, icon: 'wrench' },
    { name: 'Other',        ecoPoints:  5, icon: 'box' },
];

// ─── Seed ─────────────────────────────────────────────────────────────────────

async function main() {
    console.log('🌱 Seeding punap_new database...');

    // Provinces + Cities
    for (const prov of PROVINCES) {
        const province = await prisma.province.upsert({
            where: { name: prov.name },
            update: {},
            create: { name: prov.name },
        });
        for (const cityName of prov.cities) {
            await prisma.city.upsert({
                where: { name_provinceId: { name: cityName, provinceId: province.id } },
                update: {},
                create: { name: cityName, provinceId: province.id },
            });
        }
    }
    console.log('✅ Provinces and cities seeded');

    // Categories
    for (const cat of CATEGORIES) {
        await prisma.category.upsert({
            where: { name: cat.name },
            update: { ecoPoints: cat.ecoPoints, icon: cat.icon ?? null },
            create: { name: cat.name, ecoPoints: cat.ecoPoints, icon: cat.icon ?? null },
        });
    }
    console.log('✅ Categories seeded');

    // Platform wallet (userId null = platform)
    const existingPlatformWallet = await prisma.wallet.findFirst({
        where: { walletType: 'PLATFORM' },
    });
    if (!existingPlatformWallet) {
        await prisma.wallet.create({
            data: { walletType: 'PLATFORM', userId: null, currency: 'NPR' },
        });
    }
    console.log('✅ Platform wallet seeded');

    // Platform settings
    const defaultSettings: { key: string; value: string }[] = [
        { key: 'commission_percent', value: '3.0' },
        { key: 'swap_fee_enabled', value: 'false' },
        { key: 'max_images_per_product', value: '8' },
        { key: 'reservation_ttl_minutes', value: '30' },
    ];
    for (const s of defaultSettings) {
        await prisma.platformSetting.upsert({
            where: { key: s.key },
            update: {},
            create: s,
        });
    }
    console.log('✅ Platform settings seeded');

    // Super-admin user
    const adminEmail = process.env.SUPER_ADMIN_EMAIL ?? 'admin@punap.com';
    const adminPassword = process.env.SUPER_ADMIN_PASSWORD ?? 'AdminPass123!';
    const hashed = await bcrypt.hash(adminPassword, 12);

    const admin = await prisma.user.upsert({
        where: { email: adminEmail },
        update: {},
        create: {
            name: 'Super Admin',
            email: adminEmail,
            password: hashed,
            role: 'SUPER_ADMIN',
            accountStatus: 'ACTIVE',
            profileStatus: 'VERIFIED',
            emailVerifiedAt: new Date(),
            termsAcceptedAt: new Date(),
        },
    });

    // Ensure super-admin has a wallet
    const existingAdminWallet = await prisma.wallet.findUnique({
        where: { userId: admin.id },
    });
    if (!existingAdminWallet) {
        await prisma.wallet.create({
            data: { walletType: 'USER', userId: admin.id, currency: 'NPR' },
        });
    }
    console.log(`✅ Super-admin seeded: ${adminEmail}`);

    console.log('🎉 Seed complete');
}

main()
    .catch((e) => {
        console.error(e);
        process.exit(1);
    })
    .finally(() => prisma.$disconnect());
