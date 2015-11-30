<?php namespace Kshabazz\Web\SigmaRemix\Tests;

use Kshabazz\Web\SigmaRemix\Parser;

/**
 * Class ParserTest
 *
 * @package \Kshabazz\Web\SigmaRemix\Tests
 * @coversDefaultClass \Kshabazz\Web\SigmaRemix\Parser
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
	private $templateDir;

	public function setUp()
	{
		$this->templateDir = FIXTURES_DIR;
	}

	/**
	 * @covers ::__construct
	 */
	public function test_initialization()
	{
		$parser = new Parser( '{TEST_1}', NULL );

		$this->assertInstanceOf( '\\Kshabazz\\Web\\SigmaRemix\\Parser' , $parser );
	}

	/**
	 * @covers ::__construct
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage fake-dir is not a valid directory.
	 */
	public function test_should_throw_exception_when_second_parameters_is_an_invalid_dir()
	{
		$parser = new Parser( '{TEST_1}', 'fake-dir' );
	}

	/**
	 * @covers ::process
	 * @covers ::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::replaceIncludes
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setBlocks
	 */
	public function test_parsing_placeholders()
	{
		$parser = new Parser('{TEST_1}', NULL);
		$data = $parser->process();

		$this->assertEquals('$TEST_1', $data );
		$this->assertNotContains('{TEST_1}', $data );
	}

	/**
	 * @covers ::process
	 * @covers ::setBlocks
	 * @covers ::replaceBlock
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::replaceIncludes
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 */
	public function test_parsing_block_with_just_text()
	{
		$template = \file_get_contents(
				$this->templateDir . DIRECTORY_SEPARATOR . 'block-1.html'
		);

		$parser = new Parser( $template, NULL );

		$data = $parser->process();

		$this->assertContains( '$BLOCK_1_ary = [ $BLOCK_1_vals ];', $data );
		$this->assertContains( 'foreach ($BLOCK_1_ary as $BLOCK_1_vars):', $data );
		$this->assertContains( 'extract($BLOCK_1_vars);', $data );
		$this->assertContains( 'endforeach; // END BLOCK_1', $data );
	}

	/**
	 * @covers ::replaceIncludes
	 * @covers ::replaceInclude
	 * @covers ::process
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setFunctions
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setBlocks
	 */
	public function test_parsing_an_include_tag()
	{
		$template = \file_get_contents(
			$this->templateDir . DIRECTORY_SEPARATOR . 'include-placeholders-1.html'
		);

		$parser = new Parser( $template, $this->templateDir );

		$data = $parser->process();

		$this->assertContains( 'Including placeholders-1.html was', $data );
		$this->assertContains( '$TEST_1', $data );
	}
}
?>