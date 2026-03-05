<?php

namespace App\Services;

use App\Models\VersionHistory;

class VersionHistoryService
{
    public function getLatestVersion()
    {
        return VersionHistory::active()->orderBy('created_at', 'desc')->first();
    }

    /**
     *
     *  최근 5개의 버전 불러온다.
     * @return mixed
     */
    public function getLatestVersionList()
    {
        return VersionHistory::active()->orderBy('created_at', 'desc')->limit(3)->get();
    }
}
