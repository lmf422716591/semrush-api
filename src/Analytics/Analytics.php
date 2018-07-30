<?php

namespace ScriptBurn\SemrushApi;


class Analytics extends Request
{
	private $keyWordReport, $urlReport, $domainReport;
	protected $apiKey, $cache, $cacheTime;

	public function keyWordReport()
	{
		if (!$this->keyWordReport)
		{
			//\Log::debug("keyWordReport: ".$this->cacheTime);

			$this->keyWordReport = new KeyWordReport($this->apiKey, $this->cache, $this->cacheTime);
		}

		return $this->keyWordReport;
	}
	public function urlReport()
	{
		if (!$this->urlReport)
		{
			$this->urlReport = new UrlReport($this->apiKey, $this->cache, $this->cacheTime);
		}

		return $this->urlReport;
	}
	public function domainReport()
	{
		if (!$this->domainReport)
		{
			$this->domainReport = new DomainReport($this->apiKey, $this->cache, $this->cacheTime);
		}

		return $this->domainReport;
	}
}
