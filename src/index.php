<?php

require_once '../vendor/autoload.php';

// 2025-04-29 14:36:06.000000

use Carbon\Carbon;

$created_at = Carbon::now()->toDateTimeString();
dump($created_at);
