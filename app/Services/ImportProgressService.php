<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ImportProgressService
{
    protected $cachePrefix = 'import_progress_';
    protected $cacheTime = 3600; // 1 hora

    public function setTotal($importId, $total)
    {
        Cache::put($this->cachePrefix . $importId, [
            'total' => $total,
            'current' => 0,
            'percentage' => 0
        ], $this->cacheTime);
    }

    public function updateProgress($importId, $current)
    {
        $data = Cache::get($this->cachePrefix . $importId);
        
        if (!$data) {
            return;
        }

        $total = $data['total'];
        $percentage = $total > 0 ? round(($current / $total) * 100) : 0;

        Cache::put($this->cachePrefix . $importId, [
            'total' => $total,
            'current' => $current,
            'percentage' => $percentage
        ], $this->cacheTime);
    }

    public function incrementProgress($importId)
    {
        $data = Cache::get($this->cachePrefix . $importId);
        
        if (!$data) {
            return;
        }

        $current = $data['current'] + 1;
        $total = $data['total'];
        $percentage = $total > 0 ? round(($current / $total) * 100) : 0;

        Cache::put($this->cachePrefix . $importId, [
            'total' => $total,
            'current' => $current,
            'percentage' => $percentage
        ], $this->cacheTime);
    }

    public function getProgress($importId)
    {
        $data = Cache::get($this->cachePrefix . $importId);
        
        if (!$data) {
            return [
                'total' => 0,
                'current' => 0,
                'percentage' => 0
            ];
        }

        return $data;
    }

    public function clearProgress($importId)
    {
        Cache::forget($this->cachePrefix . $importId);
    }
} 