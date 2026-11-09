<?php

namespace App;

use Illuminate\Foundation\Application as BaseApplication;

class Application extends BaseApplication
{
    // ponytail: skip framework DB/redis/s3 config merge — this app is MySQL + local disk only
    public function shouldMergeFrameworkConfiguration(): bool
    {
        return false;
    }
}
