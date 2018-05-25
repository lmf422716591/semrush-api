<?php

namespace ScriptBurn\SemrushApi;

use Illuminate\Support\Facades\Cache;

class Manager
{
    private $apiKey;

    private $analytics;
    private $cache;
    private $cacheTime;

    public function __construct($apiKey, $cacheObject, $cacheTime)
    {
        //\Log::debug("SEMRush: $apiKey,, $cacheTime");
        $this->apiKey = $apiKey;
        $this->cacheTime = (int) $cacheTime;
        $this->cache = $this->cacheTime ? $cacheObject : null;
    }

    public function analytics()
    {
        if (!$this->analytics) {
            $this->analytics = new Analytics(
                $this->apiKey,
                $this->cache,
                $this->cacheTime
            );
        }

        return $this->analytics;
    }
}
