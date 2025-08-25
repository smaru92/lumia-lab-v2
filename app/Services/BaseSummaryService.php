<?php

namespace App\Services;

use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\VersionHistory;

abstract class BaseSummaryService
{
    use ErDevTrait;

    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    protected string $logChannel;

    public function __construct(string $logChannel)
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
        $this->logChannel = $logChannel;
    }

    abstract protected function getSummaryModel(): string;
    abstract protected function getGameResults(array $params): iterable;
    abstract protected function transformData(object|array $gameResult, string $minTier, int $minScore, int $versionSeason, int $versionMajor, int $versionMinor): array;

    public function updateSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        Log::channel($this->logChannel)->info('S: update summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;

        $summaryModel = $this->getSummaryModel();

        DB::beginTransaction();
        try {
            $summaryModel::where('version_season', $versionSeason)
                ->where('version_major', $versionMajor)
                ->where('version_minor', $versionMinor)
                ->delete();

            foreach ($this->tierRange as $tier) {
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber']) ?: 0;
                $minTier = $tier['tier'] . $tier['tierNumber'];

                $gameResults = $this->getGameResults([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                $chunkData = [];
                $chunkSize = 100;

                foreach ($gameResults as $gameResult) {
                    $chunkData[] = $this->transformData($gameResult, $minTier, $minScore, $versionSeason, $versionMajor, $versionMinor);

                    if (count($chunkData) >= $chunkSize) {
                        $summaryModel::insert($chunkData);
                        $chunkData = [];
                    }
                }

                if (!empty($chunkData)) {
                    $summaryModel::insert($chunkData);
                }
            }

            DB::commit();
            Log::channel($this->logChannel)->info('E: update summary');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel($this->logChannel)->error('Error: ' . $e->getMessage());
            Log::channel($this->logChannel)->error($e->getTraceAsString());
            throw $e;
        }
    }
}
