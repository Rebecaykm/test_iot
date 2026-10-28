<?php

namespace App\Jobs;

use App\Models\InforSyncSetting;
use App\Models\YF013Proto;

class SyncProductionRecordsProto extends SyncProductionRecordsToInfor
{
    protected function environment(): string
    {
        return InforSyncSetting::ENVIRONMENT_PROTO;
    }

    protected function inforModel(): string
    {
        return YF013Proto::class;
    }

    protected function logChannel(): string
    {
        return 'infor_proto';
    }

    protected function inforProcedure(): ?string
    {
        return 'LX834OU02.YSF013C';
    }
}
