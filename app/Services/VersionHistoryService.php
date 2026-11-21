<?php

namespace App\Services;

use App\Models\VersionHistory;

class VersionHistoryService
{
    public function getLatestVersion()
    {
        return VersionHistory::active()->newestVersionFirst()->first();
    }

    /**
     *
     *  최근 5개의 버전 불러온다.
     * @return mixed
     */
    public function getLatestVersionList()
    {
        return VersionHistory::active()->newestVersionFirst()->limit(3)->get();
    }
}
