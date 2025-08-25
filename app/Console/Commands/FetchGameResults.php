<?php

namespace App\Console\Commands;

use App\Models\JobStatus;
use App\Services\GameResultService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchGameResults extends Command
{
    protected $signature = 'fetch:game-results';
    protected $description = '일정 주기마다 게임 데이터를 수집한다. (매초 최대 10건)';

    /**
     * @throws GuzzleException
     */
    public function handle(GameResultService $gameResultService)
    {
        $jobStatus = JobStatus::where('job_name', 'fetch_game_result')->first();
        if ($jobStatus) {
            $lastGameresultId = $gameResultService->storeGameResult($jobStatus->last_processed_game_id);
            $jobStatus->last_processed_game_id = $lastGameresultId;
            $jobStatus->save();
            Log::channel('fetchGameResultData')->info("fetch game work complete");
        } else {
            Log::channel('fetchGameResultData')->info("Last game result not found");
        }

    }
}
