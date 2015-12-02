<?php namespace Kshabazz\SigmaRemix\Tests;

use Kshabazz\SigmaRemix\TemplateException;


/**
 * Class TemplateExceptionTest
 *
 * @package \Kshabazz\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\SigmaRemix\TemplateException
 */
class TemplateExceptionTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @expectedException \Kshabazz\SigmaRemix\TemplateException
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