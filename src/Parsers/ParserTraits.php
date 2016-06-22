<?php namespace Kshabazz\SigmaRemix\Parsers;

/**
 * Class ParserTraits
 *
 * @package Kshabazz\SigmaRemix\Parsers
 */
trait ParserTraits
{
	/** @var bool Switch debug messages on/off. */
	static protected $debug = FALSE;

	/**
	 * Limit the number of recursive calls a function can make.
	 *
	 * This is reset once the limit is hit and the function returns. Strict mode will cause an error to be throw when
	 * the limit is reached.
	 *
	 * @var int
	 * @see ::replaceBlock, ::replaceInclude, ::replaceReplaceTag
	 * TODO: Implement and add static functions to set.
	 */
	static private $recursionLimit = 10;

	/** @var bool Throw errors for simple mistakes that are not fatal. */
	static private $strictMode = FALSE;

	/** @param bool $pSwitch Turn debugging messages on/off. */
	static public function setDebug( $pSwitch )
	{
		static::$debug = $pSwitch;
	}

	/**
	 * When on, will throw exceptions when:
	 * Cannot find an include file.
	 *
	 * @param bool $pBool TRUE or FALSE, to turn on or off respectively.
	 */
	static public function setStrict( $pBool )
	{
		static::$strictMode = $pBool;
	}

	/**
	 * Get indication if the parser is in strict mode.
	 *
	 * @return bool
	 */
	static public function isStrict()
	{
		return static::$strictMode;
	}
}
?>