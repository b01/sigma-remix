<?php namespace Kshabazz\Web\SigmaRemix\Tests;

use Kshabazz\Web\SigmaRemix\Template;

/**
 * Class TemplateTest
 *
 * @package \Kshabazz\Web\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\Web\SigmaRemix\Template
 */
class TemplateTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
	}

	/**
	 * @covers ::__construct
	 */
	public function test_construct()
	{
		$processor = new Template();
		$this->assertInstanceOf('\\Kshabazz\\Web\\SigmaRemix\\Template', $processor);
	}
}
?>
