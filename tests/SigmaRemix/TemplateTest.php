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
	private $templateDir;

	public function setUp()
	{
		$this->templateDir = FIXTURES_DIR;
	}

	/**
	 * @covers ::__construct
	 */
	public function test_construct()
	{
		$processor = new Template($this->templateDir . DIRECTORY_SEPARATOR . 'test-1.html');

		$this->assertInstanceOf('\\Kshabazz\\Web\\SigmaRemix\\Template', $processor);
	}

	/**
	 * @expectedException \Kshabazz\Web\SigmaRemix\TemplateException
	 * @expectedExceptionMessage The template "test" does not exists
	 * @covers ::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\SigmaRemixException
	 *
	 */
	public function test_construct_with_bad_file()
	{
		$processor = new Template('test');
	}

	/**
	 * @covers ::setRootDir
	 * @uses \Kshabazz\Web\SigmaRemix\Template::__construct
	 */
	public function test_set_template_root_directory()
	{
		Template::setRootDir( $this->templateDir . DIRECTORY_SEPARATOR );

		$processor = new Template('test-1.html');

		Template::setRootDir( NULL );

		$this->assertInstanceOf('\\Kshabazz\\Web\\SigmaRemix\\Template', $processor);
	}

	/**
	 * @expectedException \Kshabazz\Web\SigmaRemix\TemplateException
	 * @expectedExceptionMessage Attempt to set template root directory to "bad-directory-name", which does not exists.
	 * @covers ::setRootDir
	 * @uses \Kshabazz\Web\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\SigmaRemixException
	 */
	public function test_set_bad_template_root_directory()
	{
		Template::setRootDir( 'bad-directory-name' );
	}

	/**
	 * @expectedException \Kshabazz\Web\SigmaRemix\TemplateException
	 * @expectedExceptionMessage Attempt to set cache directory to "bad-directory-name", which does not exists.
	 * @covers ::setCacheDir
	 * @uses \Kshabazz\Web\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\SigmaRemixException
	 */
	public function test_set_bad_cache_directory()
	{
		Template::setCacheDir( 'bad-directory-name' );
	}
}
?>