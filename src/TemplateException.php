<?php namespace Kshabazz\Web\SigmaRemix;

/**
 * Class TemplateException
 *
 * @package \Kshabazz\Web\SigmaRemix
 */
class TemplateException extends SigmaRemixException
{
	const
		BAD_TEMPLATE_FILE = 1;

	/**
	 * @var array Error messages.
	 */
	protected $messages = [
		self::BAD_TEMPLATE_FILE => 'Unable to load template file "%s".'
	];
}
?>