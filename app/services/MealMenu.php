<?php
namespace App\Services;

use Nette\Database\Context;
use Nette\Utils\Strings;

class MealMenu
{
	private $baseDir;
	/** @var \Nette\Database\Context */
	private $database;

	public function __construct($baseDir, Context $database)
	{
		$this->baseDir = $baseDir;
		$this->database = $database;
	}

	public function forDate($placeId, $date = null)
	{
		$file = $this->resolveFile($placeId, $date);
		if (!file_exists($file))
			return array();

		$content = file_get_contents($file);
		$data = (array) unserialize($content);
		return $this->fix($data);
	}

	public function store($placeId, $date, $data)
	{
		$content = serialize($data);
		file_put_contents($this->resolveFile($placeId, $date), $content);
	}

	private function resolveFile($placeId, $date)
	{
		if (is_null($date))
		{
			$date = time();
		}

		$datePlain = (is_int($date)) ? date('Ymd', $date) : $date;
		return $this->baseDir . '/' . $placeId . '/' . $datePlain . '.tmp';
	}

	private function fix($input)
	{
		$sections = Array();

		foreach($input AS $type=> $arr2) {
			$type = iconv('windows-1250', 'utf-8', $type);

			if (strtolower($type) == 'jídla na obj.') $type = 'Minutka';
			if ($type == 'Pizza balená') continue;
			if ($type=='Pizza') continue;

			$foods = Array();

			foreach($arr2 AS $index=>$item)
			{
				$name = $item;

				$price_zcu = 0;
				$price_zam = 0;
				$price_ext = 0;

				$allergens = array();
				$premium = false;

				if (is_array($item))
				{
					$name = $item['name'];
					$price_zcu = $item['price_zcu'];
					$price_zam = $item['price_zam'];
					$price_ext = $item['price_ext'];
					$allergens = (isset($item['alergens'])) ? $item['alergens'] : array();
					$premium = isset($item['premium']) && $item['premium'];
				}

				$name = iconv('windows-1250', 'utf-8', $name);
				$hash = self::hash($name);
				list($id) = explode('-', $hash);
				$quality = $this->getQuality($id);

				if ($quality === false)
					$quality = -1;

				$name = str_replace(', ', ',', $name);
				$name = str_replace(',', ', ', $name);

				$foods[] = Array(
					'id'=>$index,
					'name'=>$name,
					'priceStudent'=>(float)$price_zcu,
					'priceStaff'=>(float)$price_zam,
					'priceExternal'=>(float)$price_ext,
					'hash'=>$hash,
					'quality'=>(float)$quality,
					'allergens'=> $allergens,
					'premium'=> $premium
				);
			}

			$sections[] = Array(
				'name'=> $type,
				'meals'=>$foods
			);

		}
		return $sections;
	}

	private function getQuality($hash) {
		return $this->database->table('menza_hodnoceni')
			->select('ROUND((bodu/hlasovalo)) AS score')
			->where('hash', $hash)
			->fetchField('score');
	}

	public static function hash($input) {
		$hash = Strings::toAscii($input);
		$hash = Str_Replace(' ', '', $hash);
		$hash = md5($hash);

		return $hash.'-'.base64_encode($input);
	}
}
