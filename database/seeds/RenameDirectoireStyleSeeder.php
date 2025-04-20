<?php

use App\Models\Style;
use Illuminate\Database\Seeder;

class RenameDirectoireStyleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $style = Style::where('name', 'Directoire')->first();

        if ($style) {
            $style->name = 'Directoire - Consulat';
            $style->save();

            $this->command->info('Style "Directoire" renommé à "Directoire - Consulat".');
        } else {
            $this->command->warn("Style (Directoire) n'existe pas.");
        }
    }
}
