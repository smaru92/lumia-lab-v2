<?php

namespace App\Services;

use App\Models\CharacterSkill;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 캐릭터 스킬 이름/설명 동기화
 *
 * 공식 API 에는 스킬 테이블이 없고, 언어 데이터(/v1/l10n/{language})에만 이름이 있다.
 * l10n 은 "키┃값" 한 줄씩인 텍스트 파일이며, 스킬은 아래 키를 쓴다.
 *   Skill/Group/Name/{skillId}  이름  (예: Skill/Group/Name/1014200┃부정의 손길)
 *   Skill/Group/Desc/{skillId}  설명  ({1} 같은 수치 자리표시자와 <color> 태그가 그대로 들어 있다)
 * skillId 는 전적의 skill_id 와 같다. 1{캐릭터 3자리}{슬롯 1자리}{변형 2자리} 형태.
 */
class CharacterSkillSyncService
{
    private const NAME_PREFIX = 'Skill/Group/Name/';
    private const DESC_PREFIX = 'Skill/Group/Desc/';

    /** 캐릭터 스킬 코드 범위 (3000000 이상은 무기 스킬, 4000000 이상은 전술 스킬) */
    private const MIN_SKILL_ID = 1000000;
    private const MAX_SKILL_ID = 1999999;

    private const CHUNK_SIZE = 500;

    /**
     * @return array{inserted:int, updated:int, total:int}
     */
    public function sync(string $language = 'Korean'): array
    {
        $lines = $this->downloadL10n($language);

        $names = [];
        $descs = [];
        foreach ($lines as $line) {
            $line = rtrim($line, "\r");
            $separator = mb_strpos($line, '┃');
            if ($separator === false) {
                continue;
            }

            $key = mb_substr($line, 0, $separator);
            $value = trim(mb_substr($line, $separator + 1));

            if (str_starts_with($key, self::NAME_PREFIX)) {
                $names[(int) substr($key, strlen(self::NAME_PREFIX))] = $value;
            } elseif (str_starts_with($key, self::DESC_PREFIX)) {
                $descs[(int) substr($key, strlen(self::DESC_PREFIX))] = $value;
            }
        }

        $existingIds = CharacterSkill::query()->pluck('id')->flip();
        $now = now();
        $rows = [];

        foreach ($names as $skillId => $name) {
            if ($skillId < self::MIN_SKILL_ID || $skillId > self::MAX_SKILL_ID || $name === '') {
                continue;
            }

            $rows[] = [
                'id' => $skillId,
                'character_id' => intdiv($skillId, 1000) % 1000,
                'slot' => intdiv($skillId, 100) % 10,
                'name' => $name,
                'tooltip' => ($descs[$skillId] ?? '') !== '' ? $descs[$skillId] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                CharacterSkill::upsert($chunk, ['id'], ['character_id', 'slot', 'name', 'tooltip', 'updated_at']);
            }
        });

        $inserted = count(array_filter($rows, fn ($row) => !isset($existingIds[$row['id']])));
        $result = [
            'inserted' => $inserted,
            'updated' => count($rows) - $inserted,
            'total' => count($rows),
        ];

        Log::info('character skills synced', $result);

        return $result;
    }

    /**
     * l10n 파일 다운로드. 메타 API 가 파일 주소(CloudFront)를 주고, 파일 자체는 키 없이 받는다.
     */
    private function downloadL10n(string $language): array
    {
        $client = new Client();

        $meta = json_decode(
            $client->get('https://open-api.bser.io/v1/l10n/' . $language, [
                'headers' => ['x-api-key' => config('erDev.apiKey')],
                'timeout' => 30,
            ])->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $path = $meta['data']['l10Path'] ?? null;
        if (!$path) {
            throw new \RuntimeException('l10n 파일 주소를 받지 못했습니다: ' . json_encode($meta));
        }

        $text = $client->get($path, ['timeout' => 60])->getBody()->getContents();

        return explode("\n", $text);
    }
}
