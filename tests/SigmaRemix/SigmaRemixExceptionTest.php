<?php namespace Kshabazz\Web\SigmaRemix\Tests;

use Kshabazz\Web\SigmaRemix\SigmaRemixException;


/**
 * Class SigmaRemixExceptionTest
 *
 * @package \Kshabazz\Web\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\Web\SigmaRemix\SigmaRemixException
 */
class SigmaRemixExceptionTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @expectedException \Kshabazz\Web\SigmaRemix\SigmaRemixException
	 * @expectedExceptionMessage An unknown error occurred
	 * @expectedExceptionCode 1
	 * @covers ::__construct
	 * @covers ::getMessageByCode
	 */
	public function test_unknown_error()
	{
		$exceptionMock = $this->getMockForAbstractClass(
				'\\Kshabazz\\Web\\SigmaRemix\\SigmaRemixException',
				[1]
		);

		throw $exceptionMock;
	}

	/**
	 * @expectedException \Kshabazz\Web\SigmaRemix\SigmaRemixException
	 * @expectedExceptionMessage An unknown error occurred
	 * @expectedExceptionCode -1
	 * @uses \Kshabazz\Web\SigmaRemix\SigmaRemixException::__construct
	 * @covers ::getMessageByCode
	 */
	public function test_unset_error_code()
	{
		$exceptionMock = $this->getMockForAbstractClass(
				'\\Kshabazz\\Web\\SigmaRemix\\SigmaRemixException', [-1]
		);

		throw $exceptionMock;
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Third parameter must be a string of length greater than zero or NULL.
	 * @expectedExceptionCode 2
	 * @covers ::__construct
	 * @covers ::getMessageByCode
	 */
	public function test_bad_custom_message()
	{
		$exceptionMock = $this->getMockForAbstractClass(
				'\\Kshabazz\\Web\\SigmaRemix\\SigmaRemixException',
				[1, NULL, 404]
		);

		throw $exceptionMock;
	}

	/**
	 * @expectedException \Kshabazz\Web\SigmaRemix\SigmaRemixException
	 * @expectedExceptionMessage test error message 1
	 * @expectedExceptionCode 1
	 * @covers ::__construct
	 * @covers ::getMessageByCode
	 */
	public function test_good_custom_message()
	{
		$exceptionMock = $this->getMockForAbstractClass(
				'\\Kshabazz\\Web\\SigmaRemix\\SigmaRemixException',
				[1, [1], 'test error message %s']
		);

		throw $exceptionMock;
	}
}
?>