<?php namespace Kshabazz\SigmaRemix\Tests;

use Kshabazz\SigmaRemix\Template;

/**
 * Class TemplateTest
 *
 * @package \Kshabazz\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\SigmaRemix\Template
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
		$processor = new Template($this->templateDir . DIRECTORY_SEPARATOR . 'placeholders-1.html');

		$this->assertInstanceOf('\\Kshabazz\\SigmaRemix\\Template', $processor);
	}

	/**
	 * @expectedException \Kshabazz\SigmaRemix\TemplateException
	 * @expectedExceptionMessage The template "test" does not exists
	 * @covers ::__construct
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 *
	 */
	public function test_construct_with_bad_file()
	{
		$processor = new Template('test');
	}

	/**
	 * @covers ::setRootDir
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 */
	public function test_set_template_root_directory()
	{
		Template::setRootDir( $this->templateDir . DIRECTORY_SEPARATOR );

		$processor = new Template('placeholders-1.html');

		Template::setRootDir( NULL );

		$this->assertInstanceOf('\\Kshabazz\\SigmaRemix\\Template', $processor);
	}

	/**
	 * @expectedException \Kshabazz\SigmaRemix\TemplateException
	 * @expectedExceptionMessage Attempt to set template root directory to "bad-directory-name", which does not exists.
	 * @covers ::setRootDir
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function test_set_bad_template_root_directory()
	{
		Template::setRootDir( 'bad-directory-name' );
	}

	/**
	 * @expectedException \Kshabazz\SigmaRemix\TemplateException
	 * @expectedExceptionMessage Attempt to set cache directory to "bad-directory-name", which does not exists.
	 * @covers ::setCacheDir
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\SigmaRemix\SigmaRemixException
	 */
	public function test_set_bad_cache_directory()
	{
		Template::setCacheDir( 'bad-directory-name' );
	}

	/**
	 * @covers ::build
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\SigmaRemix\Template::compilePlaceholders
	 * @uses \Kshabazz\SigmaRemix\Template::render
	 * @uses \Kshabazz\SigmaRemix\Template::save
	 * @uses \Kshabazz\SigmaRemix\Parser
	 */
	public function test_loading_a_template_with_one_placeholder()
	{
		$template = new Template( $this->templateDir . DIRECTORY_SEPARATOR . 'placeholders-1.html' );
		$compiled = $template->render([ 'TEST_1' => '4321' ]);
		$this->assertEquals( '4321', $compiled );

		return $template;
	}

	/**
	 * @covers ::save
	 * @uses \Kshabazz\SigmaRemix\Template::setCacheDir
	 * @depends test_loading_a_template_with_one_placeholder
	 */
	public function test_should_save_complied_template( Template $pTemplate )
	{
		$cacheDir = $this->templateDir . DIRECTORY_SEPARATOR . 'cache';

		Template::setCacheDir( $cacheDir );

		$this->assertTrue( $pTemplate->save() );
		$this->assertTrue( \file_exists($cacheDir . DIRECTORY_SEPARATOR . 'placeholders-1.html.php') );
	}

	/**
	 * @covers ::setPlaceholders
	 * @covers ::getPlaceholders
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 */
	public function test_should_set_a_placeholder()
	{
		$template = new Template(
			$this->templateDir . DIRECTORY_SEPARATOR . 'placeholders-1.html'
		);

		$template->setPlaceholders([ 'TEST_1' => 1234 ]);

		$this->assertEquals( 1234, $template->getPlaceholders()['TEST_1'] );
	}

	/**
	 * @covers ::parseBlock
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\SigmaRemix\Template::build
	 * @uses \Kshabazz\SigmaRemix\Template::compilePlaceholders
	 * @uses \Kshabazz\SigmaRemix\Template::render
	 * @uses \Kshabazz\SigmaRemix\Parser
	 */
	public function test_should_parse_a_block()
	{
		$template = new Template(
			$this->templateDir . DIRECTORY_SEPARATOR . 'block-1.html'
		);

		// This should cause the block to repeat it's content 3 times.
		for ( $i = 0; $i < 2; $i++ )
		{
			$template->parseBlock('BLOCK_1');
		}

		$actual = $template->render();

		$expected = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'expected-output-1.txt'
		);

		$this->assertContains( $expected, $actual );
	}

	/**
	 * @covers ::render
	 * @covers ::compilePlaceholders
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\SigmaRemix\Template::build
	 * @uses \Kshabazz\SigmaRemix\Parser
	 */
	public function test_should_render_a_template()
	{
		$template = new Template(
			$this->templateDir . DIRECTORY_SEPARATOR . 'placeholders-1.html'
		);

		$actual = $template->render([ 'TEST_1' => 1234 ]);

		$this->assertEquals( 1234, $actual );
	}

	/**
	 * @covers ::render
	 * @covers ::parseBlock
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\SigmaRemix\Template::build
	 * @uses \Kshabazz\SigmaRemix\Template::compilePlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser
	 */
	public function test_should_render_nested_block()
	{
		$template = new Template(
			$this->templateDir . DIRECTORY_SEPARATOR . 'nested-blocks.html'
		);

		$actual = $template->render();

		$expected = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'expected-output-nested.txt'
		);

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @covers ::render
	 * @covers ::parseBlock
	 * @uses \Kshabazz\SigmaRemix\Template::setRootDir
	 * @uses \Kshabazz\SigmaRemix\Template::__construct
	 * @uses \Kshabazz\SigmaRemix\Template::build
	 * @uses \Kshabazz\SigmaRemix\Template::compilePlaceholders
	 * @uses \Kshabazz\SigmaRemix\Parser
	 */
	public function test_should_render_a_complex_template()
	{
		Template::setRootDir( $this->templateDir );

		$template = new Template( DIRECTORY_SEPARATOR . 'complex.html' );

		// This should cause the block to repeat it's content 3 times.
		for ( $i = 1; $i < 3; $i++ )
		{
			$template->parseBlock( 'NESTED_BLOCK_1', ['TEST_1' => $i . '.'] );
		}

		$actual = $template->render();

		$expected = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'expected-output-complex.txt'
		);

		$this->assertEquals( $expected, $actual );

		// Reset for next test.
		Template::setRootDir( $this->templateDir );
	}
}
?>