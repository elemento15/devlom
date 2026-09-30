<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'PRC', 'name' => 'In Process'],
            ['code' => 'FIN', 'name' => 'Finished'],
        ] as $status) {
            Status::updateOrCreate(['code' => $status['code']], $status);
        }
    }
}
