<?php

namespace App\Http\Controllers;

use App\Helper\ERDev;
use App\Models\Character;
use App\Models\Equipment;
use App\Traits\ErDevTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * er dev 서버에서 기본정보 데이터를 불러오는 컨트롤러
 */
class InfoController
{
    use ErDevTrait;
    public function getCharacters(Request $request)
    {
        $data = ERDev::sendCurl([], 'v2/data/Character');
        $data = $data['data'];
        foreach ($data as $character) {
            $characters = new Character();
            $columns = $this->getColumns('characters');
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $characters->$column = $item;
                }

            }
            $characters->save();
        }
        return response()->json(['msg' => '완료']);
    }
    public function getEquipments(Request $request)
    {
        $data = ERDev::sendCurl([], 'v2/data/ItemWeapon');
        $data = $data['data'];
        foreach ($data as $character) {
            $equipments = new Equipment();
            $columns = $this->getColumns('equipments');
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if ($column == 'itemType' || $column == 'item_type') {
                    $column = 'item_type1';
                }
                if ($column == 'weaponType' || $column == 'weapon_type') {
                    $column = 'item_type2';
                }
                if (in_array($column, $columns)) {
                    $equipments->$column = $item;
                }

            }
            $equipments->save();
        }
        $data = ERDev::sendCurl([], 'v2/data/ItemArmor');
        $data = $data['data'];
        foreach ($data as $character) {
            $equipments = new Equipment();
            $columns = $this->getColumns('equipments');
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if ($column == 'itemType' || $column == 'item_type') {
                    $column = 'item_type1';
                }
                if ($column == 'armorType' || $column == 'armor_type') {
                    $column = 'item_type2';
                }
                if (in_array($column, $columns)) {
                    $equipments->$column = $item;
                }

            }
            $equipments->save();
        }
        return response()->json(['msg' => '완료']);
    }
    public function getItems(Request $request)
    {
        $data = ERDev::sendCurl([], 'v2/data/Character');
        $data = $data['data'];
        foreach ($data as $character) {
            $characters = new Character();
            $columns = $this->getColumns('characters');
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $characters->$column = $item;
                }

            }
            $characters->save();
        }
        return response()->json(['msg' => '완료']);
    }
    public function getSkills(Request $request)
    {
        $data = ERDev::sendCurl([], 'v2/data/Character');
        $data = $data['data'];
        foreach ($data as $character) {
            $characters = new Character();
            $columns = $this->getColumns('characters');
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $characters->$column = $item;
                }

            }
            $characters->save();
        }
        return response()->json(['msg' => '완료']);
    }
    public function getTrait(Request $request)
    {
        $data = ERDev::sendCurl([], 'v2/data/Character');
        $data = $data['data'];
        foreach ($data as $character) {
            $characters = new Character();
            $columns = $this->getColumns('characters');
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $characters->$column = $item;
                }

            }
            $characters->save();
        }
        return response()->json(['msg' => '완료']);
    }
}
