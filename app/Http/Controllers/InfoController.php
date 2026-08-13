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
     * 한 번에 insert/upsert 할 최대 row 수
     * (equipments는 컬럼이 60개가 넘어서 한 방에 처리하면 MySQL 플레이스홀더 한계(65535)를 넘김)
     */
    private const CHUNK_SIZE = 500;

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

        $columns = $this->getColumns('characters');
        // created_at, updated_at은 별도 처리
        $dataColumns = array_values(array_filter($columns, function ($column) {
            return !in_array($column, ['created_at', 'updated_at']);
        }));

        // id 기준으로 중복 제거 + 모든 row를 동일한 컬럼 구조로 정규화
        $rows = [];
        foreach ($data['data'] as $character) {
            $row = [];
            foreach ($character as $key => $item) {
                $column = Str::snake($key);
                if ($column == 'code') {
                    $column = 'id';
                }
                if (in_array($column, $columns)) {
                    $row[$column] = $item;
                }
            }

            if (empty($row['id'])) {
                continue;
            }

            $normalized = [];
            foreach ($dataColumns as $column) {
                $normalized[$column] = $row[$column] ?? null;
            }
            $rows[$normalized['id']] = $normalized;
        }

        if (empty($rows)) {
            return response()->json(['msg' => '가져올 캐릭터 데이터가 없습니다.']);
        }

        // DB에 없는 id는 insert, 이미 있는 id는 update로 분리
        $existingIds = array_flip(Character::pluck('id')->all());
        $insertRows = [];
        $updateRows = [];
        foreach ($rows as $id => $row) {
            if (isset($existingIds[$id])) {
                $updateRows[] = $row;
            } else {
                $insertRows[] = $row;
            }
        }

        // 신규 캐릭터는 insert (name도 이때 최초로 저장됨)
        $now = now();
        foreach (array_chunk($insertRows, self::CHUNK_SIZE) as $chunk) {
            Character::insert(array_map(function ($row) use ($now) {
                return $row + ['created_at' => $now, 'updated_at' => $now];
            }, $chunk));
        }

        // 기존 캐릭터는 update (name은 수동 관리값이므로 보호)
        $updateColumns = array_values(array_filter($dataColumns, function ($column) {
            return !in_array($column, ['id', 'name']);
        }));
        foreach (array_chunk($updateRows, self::CHUNK_SIZE) as $chunk) {
            Character::upsert($chunk, ['id'], $updateColumns);
        }

        return response()->json([
            'msg' => '완료',
            'inserted' => count($insertRows),
            'updated' => count($updateRows),
        ]);
    }
    /**
     * ER API 아이템 1건을 equipments 테이블 컬럼 구조로 변환
     *
     * @param array $item ER API 아이템 데이터
     * @param array $columns equipments 테이블 컬럼 목록
     * @return array
     */
    private function mapEquipmentRow(array $item, array $columns)
    {
        $row = [];
        foreach ($item as $key => $value) {
            $column = Str::snake($key);
            if ($column == 'code') {
                $column = 'id';
            }
            if ($column == 'item_type') {
                $column = 'item_type1';
            }
            // 무기는 weaponType, 방어구는 armorType으로 내려옴
            if ($column == 'weapon_type' || $column == 'armor_type') {
                $column = 'item_type2';
            }
            if (in_array($column, $columns)) {
                $row[$column] = $value;
            }
        }
        return $row;
    }

    public function getEquipments(Request $request)
    {
        $columns = $this->getColumns('equipments');
        // created_at, updated_at은 별도 처리
        $dataColumns = array_values(array_filter($columns, function ($column) {
            return !in_array($column, ['created_at', 'updated_at']);
        }));

        // ItemWeapon / ItemArmor 데이터 처리 (id 기준으로 중복 제거)
        $rows = [];
        foreach (['v2/data/ItemWeapon', 'v2/data/ItemArmor'] as $endpoint) {
            $data = $this->fetchFromErApi($endpoint);

            foreach ($data['data'] as $item) {
                $row = $this->mapEquipmentRow($item, $columns);
                if (empty($row['id'])) {
                    continue;
                }

                // 모든 row를 동일한 컬럼 구조로 정규화
                $normalized = [];
                foreach ($dataColumns as $column) {
                    $normalized[$column] = $row[$column] ?? null;
                }
                $rows[$normalized['id']] = $normalized;
            }
        }

        if (empty($rows)) {
            return response()->json(['msg' => '가져올 장비 데이터가 없습니다.']);
        }

        // DB에 없는 id는 insert, 이미 있는 id는 update로 분리
        $existingIds = array_flip(Equipment::pluck('id')->all());
        $insertRows = [];
        $updateRows = [];
        foreach ($rows as $id => $row) {
            if (isset($existingIds[$id])) {
                $updateRows[] = $row;
            } else {
                $insertRows[] = $row;
            }
        }

        // 신규 장비는 insert (name도 이때 최초로 저장됨)
        $now = now();
        foreach (array_chunk($insertRows, self::CHUNK_SIZE) as $chunk) {
            Equipment::insert(array_map(function ($row) use ($now) {
                return $row + ['created_at' => $now, 'updated_at' => $now];
            }, $chunk));
        }

        // 기존 장비는 update (name, item_type3는 수동 관리값이므로 보호)
        $updateColumns = array_values(array_filter($dataColumns, function ($column) {
            return !in_array($column, ['id', 'name', 'item_type3']);
        }));
        foreach (array_chunk($updateRows, self::CHUNK_SIZE) as $chunk) {
            Equipment::upsert($chunk, ['id'], $updateColumns);
        }

        return response()->json([
            'msg' => '완료',
            'inserted' => count($insertRows),
            'updated' => count($updateRows),
        ]);
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
