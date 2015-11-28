<?php namespace Kshabazz\Web\SigmaRemix\Tests;

use Kshabazz\Web\SigmaRemix\TemplateException;


/**
 * Class TemplateExceptionTest
 *
 * @package \Kshabazz\Web\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\Web\SigmaRemix\TemplateException
 */
class TemplateExceptionTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @expectedException \Kshabazz\Web\SigmaRemix\TemplateException
	 * @expectedExceptionMessage The template "test" does not exists
	 * @expectedExceptionCode 1
	 * @covers ::__construct
	 * @covers ::getMessageByCode
	 */
	public function test_bad_template()
	{
		throw new TemplateException(TemplateException::BAD_TEMPLATE_FILE, ['test']);
	}
}
?>