<?php

use Illuminate\Database\Seeder;

use App\OauthClient;

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
        
        for($i = 0; $i < self::ITERATIONS; $i++)
        {
            OAuthClient::create([
                'id'     => 'id' . $i,
                'secret' => 'secret' . $i,
                'name'   => 'name' . $i
            ]);
        }
    }
    
    /**
     * Truncate all tables and Redis Keys.
     * 
     * @return void
     */
    private function resetTables()
    {
        \Schema::disableForeignKeyConstraints();
        OauthClient::truncate();
        \Schema::enableForeignKeyConstraints();
    }
}