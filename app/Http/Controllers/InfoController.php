<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Equipment;
use App\Traits\ErDevTrait;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * er dev 서버에서 기본정보 데이터를 불러오는 컨트롤러
 */
class InfoController
{
    use ErDevTrait;

    /**
     * ER API에서 데이터를 가져오는 메서드
     */
    private function fetchFromErApi($endpoint)
    {
        try {
            $client = new Client();
            $response = $client->get(
                "https://open-api.bser.io/" . $endpoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-api-key' => config('erDev.apiKey'),
                    ]
                ]
            );
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch data from ER API: ' . $e->getMessage());
        }
    }
    public function getCharacters(Request $request)
    {
        $data = $this->fetchFromErApi('v2/data/Character');
        $data = $data['data'];

        $upsertData = [];
        $columns = $this->getColumns('characters');

        foreach ($data as $character) {
            $row = [];
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $row[$column] = $item;
                }
            }
            $upsertData[] = $row;
        }

        // name 컬럼을 제외한 업데이트 컬럼 목록
        $updateColumns = array_filter($columns, function($column) {
            return $column !== 'name' && $column !== 'id';
        });

        Character::upsert(
            $upsertData,
            ['id'], // unique key
            $updateColumns // name 제외한 업데이트 컬럼
        );

        return response()->json(['msg' => '완료']);
    }
    public function getEquipments(Request $request)
    {
        $upsertData = [];
        $columns = $this->getColumns('equipments');

        // ItemWeapon 데이터 처리
        $weaponData = $this->fetchFromErApi('v2/data/ItemWeapon');
        $weaponData = $weaponData['data'];

        foreach ($weaponData as $item) {
            $row = [];
            foreach($item as $key => $value) {
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
                    $row[$column] = $value;
                }
            }
            $upsertData[] = $row;
        }

        // ItemArmor 데이터 처리
        $armorData = $this->fetchFromErApi('v2/data/ItemArmor');
        $armorData = $armorData['data'];

        foreach ($armorData as $item) {
            $row = [];
            foreach($item as $key => $value) {
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
                    $row[$column] = $value;
                }
            }
            $upsertData[] = $row;
        }

        // ✅ 모든 row를 동일한 컬럼 구조로 정규화
        $upsertData = array_map(function ($row) use ($columns) {
            $normalized = [];
            foreach ($columns as $column) {
                // created_at, updated_at은 제외 (Laravel이 자동 처리)
                if (in_array($column, ['created_at', 'updated_at'])) {
                    continue;
                }
                $normalized[$column] = $row[$column] ?? null;
            }
            return $normalized;
        }, $upsertData);

        // name 컬럼을 제외한 업데이트 컬럼 목록
        $updateColumns = array_filter($columns, function($column) {
            return !in_array($column, ['name', 'id', 'created_at', 'updated_at']);
        });

        Equipment::upsert(
            $upsertData,
            ['id'],
            array_values($updateColumns) // array_values로 인덱스 재정렬
        );

        return response()->json(['msg' => '완료']);
    }
    public function getItems(Request $request)
    {
        $data = $this->fetchFromErApi('v2/data/Character');
        $data = $data['data'];

        $upsertData = [];
        $columns = $this->getColumns('characters');

        foreach ($data as $character) {
            $row = [];
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $row[$column] = $item;
                }
            }
            $upsertData[] = $row;
        }

        // name 컬럼을 제외한 업데이트 컬럼 목록
        $updateColumns = array_filter($columns, function($column) {
            return $column !== 'name' && $column !== 'id';
        });

        Character::upsert(
            $upsertData,
            ['id'], // unique key
            $updateColumns // name 제외한 업데이트 컬럼
        );

        return response()->json(['msg' => '완료']);
    }
    public function getSkills(Request $request)
    {
        $data = $this->fetchFromErApi('v2/data/Character');
        $data = $data['data'];

        $upsertData = [];
        $columns = $this->getColumns('characters');

        foreach ($data as $character) {
            $row = [];
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $row[$column] = $item;
                }
            }
            $upsertData[] = $row;
        }

        // name 컬럼을 제외한 업데이트 컬럼 목록
        $updateColumns = array_filter($columns, function($column) {
            return $column !== 'name' && $column !== 'id';
        });

        Character::upsert(
            $upsertData,
            ['id'], // unique key
            $updateColumns // name 제외한 업데이트 컬럼
        );

        return response()->json(['msg' => '완료']);
    }
    public function getTraits(Request $request)
    {
        $data = $this->fetchFromErApi('v2/data/Character');
        $data = $data['data'];

        $upsertData = [];
        $columns = $this->getColumns('characters');

        foreach ($data as $character) {
            $row = [];
            foreach($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $row[$column] = $item;
                }
            }
            $upsertData[] = $row;
        }

        // name 컬럼을 제외한 업데이트 컬럼 목록
        $updateColumns = array_filter($columns, function($column) {
            return $column !== 'name' && $column !== 'id';
        });

        Character::upsert(
            $upsertData,
            ['id'], // unique key
            $updateColumns // name 제외한 업데이트 컬럼
        );

        return response()->json(['msg' => '완료']);
    }
}
