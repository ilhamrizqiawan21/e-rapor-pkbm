import { execFileSync } from 'node:child_process';
import { closeSync, existsSync, openSync } from 'node:fs';
import { resolve } from 'node:path';

export default function globalSetup(): void {
    if (process.env.BASE_URL) {
        return;
    }

    const databasePath = resolve('database/database.e2e.sqlite');

    if (!existsSync(databasePath)) {
        closeSync(openSync(databasePath, 'a'));
    }

    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--seeder=E2eSeeder', '--force'], {
        stdio: 'inherit',
        env: {
            ...process.env,
            APP_ENV: 'local',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: databasePath,
            SESSION_DRIVER: 'file',
            CACHE_STORE: 'file',
            QUEUE_CONNECTION: 'sync',
            MAIL_MAILER: 'array',
        },
    });
}
