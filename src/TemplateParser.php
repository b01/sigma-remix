<?php namespace Kshabazz\Web\SigmaRemix;

/**
 * Interface TemplateParser
 *
 * @package \Kshabazz\Web\SigmaRemix
 */
interface TemplateParser
{
	/**
	 * Get block names parsed.
	 * @return mixed
	 */
	public function getBlocks();

	/**
	 * Get placeholder names parsed.
	 * @return mixed
	 */
	public function getPlaceholders();

	/**
	 *Parse the template, converting various parts to PHP.
	 *
	 * @return string
	 */
	public function process();
}