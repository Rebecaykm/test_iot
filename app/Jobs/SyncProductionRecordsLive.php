<?php

namespace App\Jobs;

use App\Models\InforSyncSetting;
use App\Models\YF013Live;

class SyncProductionRecordsLive extends SyncProductionRecordsToInfor
{
    protected function environment(): string
    {
        return InforSyncSetting::ENVIRONMENT_LIVE;
    }

    protected function inforModel(): string
    {
        return YF013Live::class;
    }

    protected function logChannel(): string
    {
        return 'infor_live';
    }

    protected function inforProcedure(): ?string
    {
        // En Live todavía no se llama: 'LX834OU.YSF013C'
        return null;
    }
}
