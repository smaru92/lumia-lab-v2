<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RankRangesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('rank_ranges')->insert([
            [
                'name' => '아이언',
                'grade1' => 'Iron',
                'grade2' => '4',
                'min_score' => 0,
                'max_score' => 149,
            ],
            [
                'name' => '아이언',
                'grade1' => 'Iron',
                'grade2' => '3',
                'min_score' => 150,
                'max_score' => 299,
            ],
            [
                'name' => '아이언',
                'grade1' => 'Iron',
                'grade2' => '2',
                'min_score' => 300,
                'max_score' => 449,
            ],
            [
                'name' => '아이언',
                'grade1' => 'Iron',
                'grade2' => '1',
                'min_score' => 450,
                'max_score' => 599,
            ],
        ]);
        DB::table('rank_ranges')->insert([
            [
                'name' => '브론즈',
                'grade1' => 'Bronze',
                'grade2' => '4',
                'min_score' => 600,
                'max_score' => 799,
            ],
            [
                'name' => '브론즈',
                'grade1' => 'Bronze',
                'grade2' => '3',
                'min_score' => 800,
                'max_score' => 999,
            ],
            [
                'name' => '브론즈',
                'grade1' => 'Bronze',
                'grade2' => '2',
                'min_score' => 1000,
                'max_score' => 1199,
            ],
            [
                'name' => '브론즈',
                'grade1' => 'Bronze',
                'grade2' => '1',
                'min_score' => 1200,
                'max_score' => 1399,
            ],
        ]);
        DB::table('rank_ranges')->insert([
            [
                'name' => '실버',
                'grade1' => 'Silver',
                'grade2' => '4',
                'min_score' => 1400,
                'max_score' => 1649,
            ],
            [
                'name' => '실버',
                'grade1' => 'Silver',
                'grade2' => '3',
                'min_score' => 1650,
                'max_score' => 1899,
            ],
            [
                'name' => '실버',
                'grade1' => 'Silver',
                'grade2' => '2',
                'min_score' => 1900,
                'max_score' => 2149,
            ],
            [
                'name' => '실버',
                'grade1' => 'Silver',
                'grade2' => '1',
                'min_score' => 450,
                'max_score' => 599,
            ],
        ]);
        DB::table('rank_ranges')->insert([
            [
                'name' => '골드',
                'grade1' => 'Gold',
                'grade2' => '4',
                'min_score' => 2400,
                'max_score' => 2699,
            ],
            [
                'name' => '골드',
                'grade1' => 'Gold',
                'grade2' => '3',
                'min_score' => 2700,
                'max_score' => 2999,
            ],
            [
                'name' => '골드',
                'grade1' => 'Gold',
                'grade2' => '2',
                'min_score' => 3000,
                'max_score' => 3299,
            ],
            [
                'name' => '골드',
                'grade1' => 'Gold',
                'grade2' => '1',
                'min_score' => 3300,
                'max_score' => 3599,
            ],
        ]);
        DB::table('rank_ranges')->insert([
            [
                'name' => '플래티넘',
                'grade1' => 'Platinum',
                'grade2' => '4',
                'min_score' => 3600,
                'max_score' => 3949,
            ],
            [
                'name' => '플래티넘',
                'grade1' => 'Platinum',
                'grade2' => '3',
                'min_score' => 3950,
                'max_score' => 4299,
            ],
            [
                'name' => '플래티넘',
                'grade1' => 'Platinum',
                'grade2' => '2',
                'min_score' => 4300,
                'max_score' => 4649,
            ],
            [
                'name' => '플래티넘',
                'grade1' => 'Platinum',
                'grade2' => '1',
                'min_score' => 4650,
                'max_score' => 4999,
            ],
        ]);
        DB::table('rank_ranges')->insert([
            [
                'name' => '다이아몬드',
                'grade1' => 'Diamond',
                'grade2' => '4',
                'min_score' => 5000,
                'max_score' => 5349,
            ],
            [
                'name' => '다이아몬드',
                'grade1' => 'Diamond',
                'grade2' => '3',
                'min_score' => 5350,
                'max_score' => 5699,
            ],
            [
                'name' => '다이아몬드',
                'grade1' => 'Diamond',
                'grade2' => '2',
                'min_score' => 5700,
                'max_score' => 6049,
            ],
            [
                'name' => '다이아몬드',
                'grade1' => 'Diamond',
                'grade2' => '1',
                'min_score' => 6050,
                'max_score' => 6399,
            ],
        ]);
        DB::table('rank_ranges')->insert([
            [
                'name' => '메테오라이트',
                'grade1' => 'Meteorite',
                'grade2' => '',
                'min_score' => 6400,
                'max_score' => 6999,
            ],
            [
                'name' => '미스릴',
                'grade1' => 'Mithril',
                'grade2' => '',
                'min_score' => 7000,
                'max_score' => 99999,
            ],
            [
                'name' => '데미갓',
                'grade1' => 'Demigod',
                'grade2' => '',
                'min_score' => 7700,
                'max_score' => 99999,
            ],
            [
                'name' => '이터니티',
                'grade1' => 'Eternity',
                'grade2' => '',
                'min_score' => 7700,
                'max_score' => 99999,
            ],
        ]);
    }
}
