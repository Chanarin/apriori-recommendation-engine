<?php

use App\OauthClient;
use Illuminate\Database\Seeder;

class OauthClientsTableSeeder extends Seeder
{
    /**
     * @var int
     */
    const ITERATIONS = 10;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->resetTables();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            OauthClient::create([
                'id'     => 'id'.$i,
                'secret' => 'secret'.$i,
                'name'   => 'name'.$i,
            ]);
        }
    }

    private function resetTables()
    {
        \Schema::disableForeignKeyConstraints();
        OauthClient::truncate();
        \Schema::enableForeignKeyConstraints();
    }
}
