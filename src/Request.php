<?php

namespace ScriptBurn\SemrushApi;
use \GuzzleHttp\Client;

class Request
{
	private $client, $endpoint;

	protected $apiKey, $cache, $cacheTime, $logger, $lastRequesturl, $lastParams;
	public function __construct($apiKey, $cache = null, $cacheTime, $logger = null)
	{
		//\Log::debug("API: $apiKey,, $cacheTime");

		$this->client = new \Curl\Curl();

		$this->apiKey = $apiKey;
		$this->endPoint = "https://api.semrush.com/";
		$this->cacheTime = $cacheTime;
		$this->cache = $this->cacheTime && $cache ? $cache : false;
		$this->logger = $logger;
		//$this->log('Caching API result: '.($this->cache ? 'yes ' : 'no '.$this->cacheTime).($this->cache ? ($this->cacheTime == "-1" ? ' forever' : (" ".$this->cacheTime." mins")) : ''));
	}
	public function lastRequest()
	{
		return $this->lastRequesturl;
	}
	public function lastParams()
	{
		return $this->lastParams;
	}
	private function log($msg, $type = "debug")
	{
		if ($this->logger)
		{
			call_user_func_array([$this->logger, $type], [$msg]);
		}
	}
	public function execAPI($type, $params, $options)
	{
		$this->client->reset();
		$this->client->setOpt(CURLOPT_FOLLOWLOCATION, true);

		$default_options = ['response_has_headers' => true, 'cache' => true];
		$options = array_merge($default_options, $options);
		$params_default = [
			'export_columns' => 'Ph,Nq,Cp,Co,Nr',
			'database' => 'uk',
			'export_escape' => '0',
		];
		$params = array_merge($params_default, $params);

		$params['key'] = $this->apiKey;
		if ($params['database'])
		{
			if (!$this->databases($params['database']))
			{
				throw new \Exception('Invalid database For API');
			}
		}
		//$params['export_columns'] = "Ph%2CPo%2CPp%2CPd%2CAb%2CNq%2CCp%2CTr%2CTc%2CCo%2CNr%2CTd%2CTt%2CDs%2CVu%2CUr";
		$url = $this->endPoint."?".http_build_query($params);
		//$params['export_columns'] = "Ph%2CPo%2CPp%2CPd%2CAb%2CNq%2CCp%2CTr%2CTc%2CCo%2CNr%2CTd%2CTt%2CDs%2CVu";
		$this->log("Running API: ".$url);
		 
		$this->lastRequesturl = $url;
		
		if (@$options['cache'] && $data = $this->hasCache($url))
		{
			$this->log('Found in cache');

			return $data;
		}

		$data = $this->fetch($params, $options);
		$this->log("API Result received");
		if (@$options['cache'] && is_array($data) && !empty($data))
		{
			$this->setCache($this->lastRequesturl, $data);
		}

		return $data;
	}
	private function cleanData($data)
	{
		$data = html_entity_decode($data);
		$data = str_replace(["&#39;"], ["'"], $data);

		return $data;
	}
	public function fetch($params, $options = [])
	{
		/*
			$params = ['export_columns' => 'Ph,Po,Pp,Pd,Nq,Cp,Ur,Tr,Tc,Co,Nr,Td', 'database' => 'uk', 'export_escape' => '0', 'domain' => 'loveholidays.com', 'type' => 'domain_adwords', 'display_limit' => '25', 'display_date' => '20150115', 'key' => '501c3f063c8547e560c7564122893a9c'];
		*/

		// $url="http://semrush.baba.loc/keyword.api.semrush.csv";
		$this->lastParams = $params;
		$this->client->get($this->endPoint, $params);
		 
		// $this->client->get( $url);
		$response = $this->cleanData($this->client->response);

		if ($this->client->error)
		{
			try
			{
				$rows = array_values(array_filter(explode(PHP_EOL, trim($response))));
				$msg = $this->translateError($rows[0]);

				throw new \Exception($this->client->error_message);
			}
			catch (\Exception $e)
			{
				throw new \Exception($e->getMessage());
			}
		}

		$data = $response;

		$items = [];
		$rows = array_values(array_filter(explode(PHP_EOL, trim($data))));

		if (count($rows) == 1)
		{
			$this->translateError($rows[0]);
		}
		else
		{
			if (!count($rows))
			{
				throw new \Exception("No result");
			}
			$default_row_data = [];
			$headers = [];
			$extra_data = [];
			if ($options['response_has_headers'])
			{
				$headers = explode(";", trim($rows[0]));
				$default_row_data = array_fill(0, count($headers), "");

				if (!empty($options['extra_fields']))
				{
					$headers = array_merge($headers, array_keys($options['extra_fields']));
					$extra_data = array_values($options['extra_fields']);
					foreach (($options['extra_fields']) as $field => $value)
					{
						$default_row_data[] = $value;
					}
				}
				unset($rows[0]);
			}

			foreach ($rows as $row)
			{
                if(!($row))
                {
                    continue;
                }
				$row = explode(";", trim($row));
				$row = $row + $default_row_data;
				if (count($headers))
				{
					if (count($headers) != count($row))
					{
						print_r($headers);
						print_r($row);
						//exit();
					}
					$row = array_slice($row, 0, count($headers));
					$row = array_combine($headers, $row);
				}
				$items[] = $row;
			}

			if (@$options['single_row'])
			{
				$items = $items[0];
			}

			return $items; //$this->setCache($url, $items);
		}
	}
	public function hasCache($key, $default = null)
	{
		if (!$this->cache)
		{
			return false;
		}
		else
		{
			return $this->cache->has($key) ? $this->cache->get($key, $default) : false;
		}
	}
	public function setCache($key, $value)
	{
		if (!$this->cache)
		{
			return $value;
		}
		if ($this->cacheTime == -1)
		{
			$this->cache->forever($key, $value);
		}
		elseif ($this->cacheTime)
		{
			$this->cache->put($key, $value, $this->cacheTime);
		}
	}

	public function translateError($response)
	{
		$matches = [];
		preg_match_all('/(.*)\s+(.*)\s+::\s+(.*)/', $response, $matches, PREG_SET_ORDER, 0);

		if (trim(strtolower($matches[0][1])) == 'error')
		{
			$error = ucwords(trim(strtolower($matches[0][3])));
			$this->log("API Error: ".$error);
			throw new \Exception($error);
		}
		//var_dump($matches);
		//WRONG KEY - ID PAIR
		//NOTHING FOUND
	}
	public function databases($index = "")
	{
		$databases = ["us" => "google.com",
			"uk" => "google.co.uk",
			"ca" => "google.ca",
			"ru" => "google.ru",
			"de" => "google.de",
			"fr" => "google.fr",
			"es" => "google.es",
			"it" => "google.it",
			"br" => "google.com.br",
			"au" => "google.com.au",
			"bing-us" => "bing.com",
			"ar" => "google.com.ar",
			"be" => "google.be",
			"ch" => "google.ch",
			"dk" => "google.dk",
			"fi" => "google.fi",
			"hk" => "google.com.hk",
			"ie" => "google.ie",
			"il" => "google.co.il",
			"mx" => "google.com.mx",
			"nl" => "google.nl",
			"no" => "google.no",
			"pl" => "google.pl",
			"se" => "google.se",
			"sg" => "google.com.sg",
			"tr" => "google.com.tr",
			"mobile-us" => "google.com",
			"jp" => "google.co.jp",
			"in" => "google.co.in",
			"hu" => "google.hu",
			"af" => "google.com.af",
			"al" => "google.al",
			"dz" => "google.dz",
			"ao" => "google.co.ao",
			"am" => "google.am",
			"at" => "google.at",
			"az" => "google.az",
			"bh" => "google.com.bh",
			"bd" => "google.com.bd",
			"by" => "google.by",
			"bz" => "google.com.bz",
			"bo" => "google.com.bo",
			"ba" => "google.ba",
			"bw" => "google.co.bw",
			"bn" => "google.com.bn",
			"bg" => "google.bg",
			"cv" => "google.cv",
			"kh" => "google.com.kh",
			"cm" => "google.cm",
			"cl" => "google.cl",
			"co" => "google.com.co",
			"cr" => "google.co.cr",
			"hr" => "google.hr",
			"cy" => "google.com.cy",
			"cz" => "google.cz",
			"cd" => "google.cd",
			"do" => "google.com.do",
			"ec" => "google.com.ec",
			"eg" => "google.com.eg",
			"sv" => "google.com.sv",
			"ee" => "google.ee",
			"et" => "google.com.et",
			"ge" => "google.ge",
			"gh" => "google.com.gh",
			"gr" => "google.gr",
			"gt" => "google.com.gt",
			"gy" => "google.gy",
			"ht" => "google.ht",
			"hn" => "google.hn",
			"is" => "google.is",
			"id" => "google.co.id",
			"jm" => "google.com.jm",
			"jo" => "google.jo",
			"kz" => "google.kz",
			"kw" => "google.com.kw",
			"lv" => "google.lv",
			"lb" => "google.com.lb",
			"lt" => "google.lt",
			"lu" => "google.lu",
			"mg" => "google.mg",
			"my" => "google.com.my",
			"mt" => "google.com.mt",
			"mu" => "google.mu",
			"md" => "google.md",
			"mn" => "google.mn",
			"me" => "google.me",
			"ma" => "google.co.ma",
			"mz" => "google.co.mz",
			"na" => "google.com.na",
			"np" => "google.com.np",
			"nz" => "google.co.nz",
			"ni" => "google.com.ni",
			"ng" => "google.com.ng",
			"om" => "google.com.om",
			"py" => "google.com.py",
			"pe" => "google.com.pe",
			"ph" => "google.com.ph",
			"pt" => "google.pt",
			"ro" => "google.ro",
			"sa" => "google.com.sa",
			"sn" => "google.sn",
			"rs" => "google.rs",
			"sk" => "google.sk",
			"si" => "google.si",
			"za" => "google.co.za",
			"kr" => "google.co.kr",
			"lk" => "google.lk",
			"th" => "google.co.th",
			"bs" => "google.bs",
			"tt" => "google.tt",
			"tn" => "google.tn",
			"ua" => "google.com.ua",
			"ae" => "google.ae",
			"uy" => "google.com.uy",
			"ve" => "google.co.ve",
			"vn" => "google.com.vn",
			"zm" => "google.co.zm",
			"zw" => "google.co.zw",
			"ly" => "google.com.ly",
			"mobile-uk" => "google.com",
			"mobile-ca" => "google.ca",
			"mobile-de" => "google.de",
			"mobile-fr" => "google.fr",
			"mobile-es" => "google.es",
			"mobile-it" => "google.it",
			"mobile-br" => "google.com.br",
			"mobile-au" => "google.com.au",
			"mobile-dk" => "google.dk",
			"mobile-mx" => "google.com.mx",
			"mobile-nl" => "google.nl",
			"mobile-se" => "google.se",
			"mobile-tr" => "google.com.tr",
			"mobile-in" => "google.co.in",
			"mobile-id" => "google.co.id",
			"mobile-il" => "google.co.il"];

		return $index ? $databases[$index] : $databases;
	}
	public function getSearchVol($type, $database, $export_columns, $phrases)
	{
		try
		{
			if (empty($phrases))
			{
				throw new \Exception('Invalid phrase');
			}

			$params = [
				'key' => 'e8a51b085939ebd45d6bdf161147e071',
				'type' => $type,
				'database' => $database,
				'export_columns' => $export_columns,
				'phrases' => $phrases,

			];
			$phrases = explode(PHP_EOL, trim($_POST['phrase']));

			$data = ['header' => ['Keyword', 'Search Volume', 'CPC', 'Competition', 'Number of Results'], 'rows' => []];

			$responses = array();
			$columns = null;
			$curl = new Curl\Curl();
			foreach ($phrases as $phrase)
			{
				$args = $params;
				$args['phrase'] = trim($phrase);
				if (!$args['phrase'])
				{
					continue;
				}
				$curl->get('https://api.semrush.com/', $args);

				//$curl=new stdclass();
				//$curl->error=0;
				//$curl->response=json_decode('"Keyword;Search Volume;CPC;Competition;Number of Results\r\ncheap;18100;1.18;0.16;419000000"');
				if ($curl->error)
				{
					throw new \Exception('Error: '.$phrase.' error_code '.$curl->error_code);
				}
				else
				{
					$rows = explode(PHP_EOL, trim(html_entity_decode($curl->response)));

					if (count($rows) > 1)
					{
						$row = explode(";", $rows[1]);
					}
					else
					{
						$row = explode(";", $rows[0]);
					}
					if (count($row) != count($data['header']))
					{
						$err = @$rows[0];
						$row = array_fill(0, count($data['header']), "");
						$row[0] = $args['phrase'];
						$row[1] = stripos($err, 'ERROR 50 :: NOTHING FOUND') !== false ? 0 : $err;
					}
					$data['rows'] = array_merge($data['rows'], [array_values($row)]);
				}
			}

			$curl->close();

			return [
				'status' => 1,
				'message' => '',
				'data' => $data,
				'params' => $params,

			];
		}
		catch (\Exception $e)
		{
			return [
				'status' => 0,
				'message' => $e->getMessage(),
				'data' => [],
				'params' => $params,
			];
		}
	}
}
