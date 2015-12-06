<?php namespace Kshabazz\SigmaRemix;

/**
 * Class ParserException
 *
 * @package Kshabazz\SigmaRemix
 */
class ParserException extends SigmaRemixException
{
	const
		BAD_INCLUDE = 1,
		RECURSION = 2;

	static protected $messages = [
		self::BAD_INCLUDE => 'Could not INCLUDE template "%s"; Please check that the file exists.',
		self::RECURSION => 'Maximum number of recursive/nested %s tags has been reached (function %s).',
	];
}
?>