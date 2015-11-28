<?php namespace Kshabazz\Web\SigmaRemix\Tests;

use Kshabazz\Web\SigmaRemix\Processor;

/**
 * Class ProcessorTest
 *
 * @package \Kshabazz\Web\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\Web\SigmaRemix\Processor
 */
class ProcessorTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
	}

	/**
	 * @covers ::__construct
	 */
	public function test_construct()
	{
		$processor = new Processor();
		$this->assertInstanceOf('\\Kshabazz\\Web\\SigmaRemix\\Processor', $processor);
	}
}
?>
