<?php

namespace ScriptBurn\SemrushApi;

class DomainReport extends Analytics
{
	public function organicHeader()
	{
		return ['Keyword', 'Position', 'Previous Position', 'Position Difference', 'Search Volume', 'CPC', 'Url', 'Traffic (%)', 'Traffic Cost (%)', 'Competition', 'Number of Results', 'Trends'];
	}
	public function paidHeader()
	{
		return ['Keyword', 'Position', 'Previous Position', 'Position Difference',  'Search Volume', 'CPC',  'Traffic (%)', 'Traffic Cost (%)', 'Competition', 'Number of Results', 'Trends', 'Title', 'Description', 'Visible Url', 'Url'];
	}
	public function rankHeader()
	{
		return ['Domain', 'Rank', 'Organic Keywords', 'Organic Traffic', 'Organic Cost', 'Adwords Keywords', 'Adwords Traffic', 'Adwords Cost'];
	}

	public function organic($domain, $database, $params = [], $options = [])
	{
		$params_default = ['export_columns' => 'Ph,Po,Pp,Pd,Nq,Cp,Ur,Tr,Tc,Co,Nr,Td'];
		$params = array_merge($params_default, $params);

		return $this->fetchDomainReport('domain_organic', $domain, $database, $params, $options);
	}

	public function paid($domain, $database, $params = [], $options = [])
	{
		$params_default = ['export_columns' => 'Ph,Po,Pp,Pd,Ab,Nq,Cp,Tr,Tc,Co,Nr,Td,Tt,Ds,Vu,Ur'];
		$params = array_merge($params_default, $params);

		return $this->fetchDomainReport('domain_adwords', $domain, $database, $params, $options);
	}
	public function rank($domain, $database, $params, $options = [])
	{
		$params_default = ['export_columns' => 'Dn,Rk,Or,Ot,Oc,Ad,At,Ac'];
		$params = array_merge($params_default, $params);



		return $this->fetchDomainReport('domain_rank', $domain, $database, $params, $options);
	}
	private function fetchDomainReport($type, $domain, $database, $params = [], $options = [])
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
		$params_default = ['domain' => $domain, 'type' => $type, 'export_columns' => 'Ph,Po,Pp,Pd,Nq,Cp,Ur,Tr,Tc,Co,Nr,Td', 'display_limit' => 25];
		$params = array_merge($params_default, $params);

		$default_options = ['response_has_headers' => true, 'single_row' => false];
		$options = array_merge($default_options, $options);
		//p_n($options);
		//p_d($params);

		return $this->execAPI($type, $params, $options);
	}
}
