<?php
namespace App\Services;

use Nette\InvalidArgumentException;
use Nette\Neon\Entity;
use Nette\Neon\Neon;
use Nette\NotImplementedException;
use Nette\Object;
use Nette\OutOfRangeException;
use Traversable;

class Places extends Object implements \Countable, \IteratorAggregate, \ArrayAccess
{
	private $places = Array();

	/**
	 * Places constructor.
	 * @param $file string Configuration file name
	 */
	public function __construct($file)
	{
		 $this->places = $this->load($file);
	}

	private static function load($file) {
		$config = Neon::decode(file_get_contents($file));
		return self::process($config, dirname($file));
	}

	private static function process($var, $baseDir) {
		if (is_array($var)) {
			$res = array();
			foreach ($var as $key => $val) {
				$res[$key] = self::process($val, $baseDir);
			}
			return $res;

		} elseif ($var instanceof Entity) {
			switch ($var->value) {
				case 'load':
				case 'file':
					return file_get_contents($baseDir . '/' . $var->attributes[0]);
				default:
					throw new InvalidArgumentException("Invalid function '$var->value'.");
			}
		}
		return $var;
	}


	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return count($this->places);
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->places);
	}

	/**
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists($offset)
	{
		return $offset >= 0 && $offset < count($this->places);
	}

	/**
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) {
			throw new OutOfRangeException('Offset invalid or out of range');
		}
		return $this->places[(int) $offset];
	}

	/**
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet($offset, $value)
	{
		throw new NotImplementedException();
	}

	/**
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset($offset)
	{
		throw new NotImplementedException();
	}
}
