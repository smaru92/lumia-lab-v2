<?php

namespace App\Services;

use App\Models\Character;
use Illuminate\Support\Facades\DB;

class CharacterService
{
    /**
     * 해당 티어의 가장 낮은 점수를 반환한다.
     * @return mixed
     */
    public function getCharacters()
    {
        $result = Character::select('id', 'name');
        return $result->get();
    }
}
