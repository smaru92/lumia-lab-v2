<?php

namespace App\Console\Commands;

use App\Services\CharacterSkillSyncService;
use Illuminate\Console\Command;

class FetchCharacterSkills extends Command
{
    protected $signature = 'fetch:character-skills {language=Korean : l10n 언어 (Korean, English ...)}';

    protected $description = '공식 API 언어 데이터에서 캐릭터 스킬 이름/설명을 가져와 character_skills 에 저장합니다.';

    public function handle(CharacterSkillSyncService $service)
    {
        try {
            $result = $service->sync($this->argument('language'));
            $this->info("캐릭터 스킬 동기화 완료 : 신규 {$result['inserted']} / 갱신 {$result['updated']} / 전체 {$result['total']}");
        } catch (\Exception $e) {
            $this->error('오류 발생: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
