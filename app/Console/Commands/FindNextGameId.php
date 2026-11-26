<?php

namespace App\Console\Commands;

use App\Models\JobStatus;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 게임 번호가 연속으로 비어서 수집이 멈췄을 때, 데이터가 있는 다음 게임 번호를 찾는다.
 *
 * fetch:game-results 는 빈 번호를 만나면 ER_SEARCH_GAME_NUMBER(기본 5)개까지만 더 찾고
 * 멈추므로, 그보다 긴 구간이 비면 매분 같은 자리에서 멈춰 있게 된다.
 * 이 명령어로 다음 게임을 찾아 job_status 를 그 앞 번호로 옮기면 다음 수집부터 이어진다.
 */
class FindNextGameId extends Command
{
    protected $signature = 'fetch:find-next-game
        {--from= : 탐색 시작 게임 번호 (기본: 현재 수집 위치 + 1)}
        {--max=3000 : 최대 탐색 개수}
        {--batch=10 : 한 번에 병렬로 요청할 개수}
        {--apply : 찾으면 확인 없이 수집 위치를 옮긴다}';

    protected $description = '게임 데이터가 있는 다음 게임 번호를 찾아 수집 위치(job_status)를 옮긴다';

    public function handle(): int
    {
        $jobStatus = JobStatus::where('job_name', 'fetch_game_result')->first();
        if (!$jobStatus) {
            $this->error('job_status 에 fetch_game_result 가 없습니다.');
            return self::FAILURE;
        }

        $current = (int) $jobStatus->last_processed_game_id;
        $from = $this->option('from') !== null ? (int) $this->option('from') : $current + 1;
        $max = max(1, (int) $this->option('max'));
        $batch = max(1, min(20, (int) $this->option('batch')));

        $this->info("현재 수집 위치: {$current}");
        $this->info("탐색: {$from} ~ " . ($from + $max - 1) . " (배치 {$batch})");

        try {
            $found = $this->search($from, $max, $batch);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($found === null) {
            $this->warn("{$max}개 안에서 게임을 찾지 못했습니다. --from 이나 --max 를 늘려 다시 실행하세요.");
            return self::FAILURE;
        }

        $newPosition = $found - 1;
        $this->info("게임 발견: {$found} (빈 구간 " . ($found - $from) . "개)");

        if ($newPosition <= $current) {
            $this->info("수집 위치({$current})보다 앞이 아니라 옮길 필요가 없습니다.");
            return self::SUCCESS;
        }

        if (!$this->option('apply')
            && !$this->confirm("수집 위치를 {$current} → {$newPosition} 로 옮길까요?", false)) {
            $this->line('옮기지 않았습니다.');
            return self::SUCCESS;
        }

        // 탐색 중에 스케줄러가 위치를 옮겼을 수 있으니 다시 읽고 비교한다
        $jobStatus->refresh();
        if ((int) $jobStatus->last_processed_game_id >= $newPosition) {
            $this->info("그 사이 수집 위치가 {$jobStatus->last_processed_game_id} 로 이미 진행돼 옮기지 않았습니다.");
            return self::SUCCESS;
        }

        $before = $jobStatus->last_processed_game_id;
        $jobStatus->last_processed_game_id = $newPosition;
        $jobStatus->save();

        Log::channel('fetchGameResultData')->info("find-next-game: 수집 위치 {$before} → {$newPosition} (다음 게임 {$found})");
        $this->info("수집 위치를 {$newPosition} 로 옮겼습니다. 다음 fetch:game-results 부터 {$found} 번을 수집합니다.");

        return self::SUCCESS;
    }

    /**
     * from 부터 차례로 배치 단위 병렬 요청을 보내, 데이터가 있는 가장 앞 번호를 돌려준다.
     */
    private function search(int $from, int $max, int $batch): ?int
    {
        $client = new Client();
        $end = $from + $max - 1;
        $bar = $this->output->createProgressBar($max);
        $bar->start();

        for ($start = $from; $start <= $end; $start += $batch) {
            $ids = range($start, min($start + $batch - 1, $end));
            $results = $this->requestBatch($client, $ids);

            // 200/404 가 아닌 응답(요청 제한 429, 타임아웃 등)이 섞이면 잠시 쉬고 다시 본다.
            // 오류를 빈 번호로 보면 실제 게임을 건너뛸 수 있다
            for ($retry = 0; $retry < 3 && $this->hasUnknown($results); $retry++) {
                sleep(2);
                $results = $this->requestBatch($client, $ids);
            }

            foreach ($ids as $id) {
                $code = $results[$id] ?? 0;
                if ($code === 200) {
                    $bar->finish();
                    $this->newLine();
                    return $id;
                }
                if ($code !== 404) {
                    $bar->finish();
                    $this->newLine();
                    throw new \RuntimeException("{$id} 번 요청이 계속 실패했습니다 (code {$code}). 잠시 후 --from={$id} 로 다시 실행하세요.");
                }
            }

            $bar->advance(count($ids));
            // 공식 API 는 초당 요청 수 제한이 있다
            usleep(1_000_000);
        }

        $bar->finish();
        $this->newLine();

        return null;
    }

    private function hasUnknown(array $results): bool
    {
        foreach ($results as $code) {
            if ($code !== 200 && $code !== 404) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, int> 게임 번호 => 응답 코드 (본문의 code, 없으면 HTTP 상태)
     */
    private function requestBatch(Client $client, array $ids): array
    {
        $promises = [];
        foreach ($ids as $id) {
            $promises[$id] = $client->getAsync('https://open-api.bser.io/v1/games/' . $id, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => config('erDev.apiKey'),
                ],
                'timeout' => 5,
                'connect_timeout' => 3,
                'http_errors' => false,
            ]);
        }

        $codes = [];
        foreach (Promise\Utils::settle($promises)->wait() as $id => $response) {
            if ($response['state'] !== 'fulfilled') {
                $codes[$id] = 0;
                continue;
            }

            $status = $response['value']->getStatusCode();
            $body = json_decode($response['value']->getBody()->getContents(), true);
            $code = is_array($body) && isset($body['code']) ? (int) $body['code'] : $status;

            // 게임이 있으면 userGames 가 온다
            if ($code === 200 && empty($body['userGames'])) {
                $code = 404;
            }

            $codes[$id] = $code;
        }

        return $codes;
    }
}
