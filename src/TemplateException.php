<?php namespace Kshabazz\SigmaRemix;

/**
 * Class TemplateException
 *
 * @package \Kshabazz\SigmaRemix
 */
class TemplateException extends SigmaRemixException
{
	const
		BAD_TEMPLATE_FILE = 1,
		BAD_CACHE_DIR = 2,
		BAD_TEMPLATE_ROOT_DIR = 3;

	/**
	 * @var array Error messages.
	 */
	static protected $messages = [
		self::BAD_TEMPLATE_FILE => 'The template "%s" does not exists.',
		self::BAD_CACHE_DIR => 'Attempt to set cache directory to "%s", which does not exists.',
		self::BAD_TEMPLATE_ROOT_DIR => 'Attempt to set template root directory to "%s", which does not exists.'
	];
}
?>