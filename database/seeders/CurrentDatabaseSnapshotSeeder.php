<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrentDatabaseSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $snapshotPath = database_path('seeders/data/current_db_snapshot.php');

        if (!file_exists($snapshotPath)) {
            $this->command?->warn('Snapshot file missing: ' . $snapshotPath);
            return;
        }

        if (DB::table('users')->exists()) {
            return;
        }

        $snapshot = require $snapshotPath;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($snapshot as $table => $rows) {
                if (empty($rows)) {
                    continue;
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}