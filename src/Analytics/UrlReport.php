<?php

namespace ScriptBurn\SemrushApi;

class UrlReport extends Analytics
{
	public function organic($url, $database, $options = [])
	{
		$options['extra_fields'] = ['type' => 'url_organic'];

		return $this->fetchUrlReport($options['extra_fields']['type'], $url, $database, $options);
	}

	public function paid($url, $database, $options = [])
	{
		$options['extra_fields'] = ['type' => 'url_adwords'];

		return $this->fetchUrlReport($options['extra_fields']['type'], $url, $database, $options);
	}

	private function fetchUrlReport($type, $url, $database, $options = [])
	{
		$url = array_values(array_filter(explode(PHP_EOL, trim($url))));
		$url = @$url[0];
		if (empty($url))
		{
			throw new \Exception('Invalid url');
		}
		elseif ( !$database)
		{
			throw new \Exception('You must provide API database');
		}
		$params = ['url' => $url, 'type' => $type,'export_columns'=>'Ph,Po,Nq,Cp,Co,Tr,Tc,Nr,Td','display_limit'=>env('API_RESULT_MAX_ROWS',25)];
		$default_options = ['response_has_headers' => true, 'single_row' => false];
		$options = array_merge($default_options, $options);
		
		return $this->execAPI($type, $params, $options);
	}
}
