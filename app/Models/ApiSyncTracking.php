<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApiSyncTracking extends Model
{
    use HasFactory;

    protected $table = 'api_sync_tracking';

    protected $fillable = [
        'api_name',
        'last_processed_id',
        'daily_request_count',
        'last_sync_date',
        'total_requests_made',
        'successful_requests',
        'failed_requests',
    ];

    protected $casts = [
        'last_sync_date' => 'date',
    ];

    public static function getTodaysTracking(string $apiName = 'perenual'): self
    {
        return self::firstOrCreate([
            'api_name' => $apiName,
            'last_sync_date' => Carbon::today(),
        ], [
            'last_processed_id' => self::getLastProcessedId($apiName),
            'daily_request_count' => 0,
            'total_requests_made' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
        ]);
    }

    private static function getLastProcessedId(string $apiName): int
    {
        $lastRecord = self::where('api_name', $apiName)
            ->where('last_sync_date', '<', Carbon::today())
            ->orderBy('last_sync_date', 'desc')
            ->first();

        return $lastRecord ? $lastRecord->last_processed_id : 0;
    }
}