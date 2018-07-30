<?php

namespace ScriptBurn\SemrushApi;

class DomainReport extends Analytics
{
	public function organic($domain, $database, $options = [])
	{
		$options['extra_fields'] = ['type' => 'domain_organic'];

		$options_default = ['export_columns' => 'Ph,Po,Pp,Pd,Nq,Cp,Ur,Tr,Tc,Co,Nr,Td'];
		$options = array_merge($options_default, $options);

		return $this->fetchDomainReport($options['extra_fields']['type'], $domain, $database, $options);
	}

	public function paid($domain, $database, $options = [])
	{
		$options['extra_fields'] = ['type' => 'domain_adwords'];
		$options_default = ['export_columns' => 'Ph,Po,Pp,Pd,Ab,Nq,Cp,Tr,Tc,Co,Nr,Td,Tt,Ds,Vu,Ur'];
		$options = array_merge($options_default, $options);
		return $this->fetchDomainReport($options['extra_fields']['type'], $domain, $database, $options);
	}

	private function fetchDomainReport($type, $domain, $database, $options = [])
	{
		$domain = array_values(array_filter(explode(PHP_EOL, trim($domain))));
		$domain = @$domain[0];
		if (empty($domain))
		{
			throw new \Exception('Invalid Domain');
		}
		elseif (!$database)
		{
			throw new \Exception('You must provide API database');
		}
		$params = ['domain' => $domain, 'type' => $type, 'export_columns' => 'Ph,Po,Pp,Pd,Nq,Cp,Ur,Tr,Tc,Co,Nr,Td', 'display_limit' => env('API_RESULT_MAX_ROWS', 25)];
		$default_options = ['response_has_headers' => true, 'single_row' => false];
		$options = array_merge($default_options, $options);

		return $this->execAPI($type, $params, $options);
	}
}
