<?php
namespace App\Services;

use Nette\Database\Context;
use Nette\Utils\DateTime;
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
		$data = $this->addMealQuality($data);
		return $data;
	}

	public function sinceDate($placeId, $date = null, $count = 7)
	{
		$files = array();

		$dir = dir($this->resolveDir($placeId));
		if ($dir === false)
			return array();

		while(false != ($entry = $dir->read()))
		{
			if (strrpos($entry, '.') != 10 || strrchr($entry, '.') != '.tmp')
				continue;
			// add filename without ext
			$files[] = strstr($entry, '.', true);
		}

		sort($files);

		$datePlain = (is_int($date)) ? date('Y-m-d', $date) : $date;

		$pos = 0;
		self::binarySearch($datePlain, $files, 'strcmp', $pos);

		$selection = array_slice($files, $pos, $count);

		$result = array();
		foreach ($selection AS $item) {
			$itemDate = DateTime::createFromFormat('Y-m-d', $item)->getTimestamp();
			$result[$itemDate] = $this->forDate($placeId, $item);
		}

		return $result;
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

		$datePlain = (is_int($date)) ? date('Y-m-d', $date) : $date;
		return $this->resolveDir($placeId) . '/' . $datePlain . '.tmp';
	}

	private function resolveDir($placeId) {
		return $this->baseDir . '/' . $placeId;
	}

	private function addMealQuality($input)
	{
		foreach ($input AS &$section)
		{
			foreach ($section['meals'] AS &$meal)
			{
				list($id) = explode('-', $meal['hash']);
				$quality = $this->getQuality($id);

				if ($quality === false)
					$quality = -1;

				$meal['quality'] = $quality;
			}
		}

		return $input;
	}

	private function getQuality($hash) {
		return $this->database->table('menza_hodnoceni')
			->select('ROUND((bodu/hlasovalo)) AS score')
			->where('hash', $hash)
			->fetchField();
	}

	public static function hash($input) {
		$hash = Strings::toAscii($input);
		$hash = Str_Replace(' ', '', $hash);
		$hash = md5($hash);

		return $hash.'-'.base64_encode($input);
	}

	private static function binarySearch($needle, $haystack, $comparator, &$probe)
	{
	    $high = Count($haystack) - 1;
	    $low = 0;

	    while ($high >= $low)
	    {
	        $probe = Floor(($high + $low ) / 2);
	        $comparison = $comparator($haystack[$probe], $needle);
	        if ($comparison < 0)
	        {
	            $low = $probe +1;
	        }
	        elseif ($comparison > 0)
	        {
		        $high = $probe -1;
	        }
	        else
	        {
		        return true;
	        }
	    }
	    //The loop ended without a match
	    //Compensate for needle greater than highest haystack element
	    if(count($haystack) == 0 || $comparator($haystack[count($haystack)-1], $needle) < 0)
	    {
		    $probe = count($haystack);
	    }
	    return false;
	}
}
