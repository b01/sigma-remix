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
	 * @covers ::setPlaceholders
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::process
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::replaceIncludes
	 */
	public function test_parsing_placeholders()
	{
		$parser = new Parser('{TEST_1}', NULL);
		$data = $parser->process();

		$this->assertEquals('$TEST_1', $data );
		$this->assertNotContains('{TEST_1}', $data );
	}
//
//	public function test_parsing_block_with_just_text()
//	{
//		$template = $this->templateDir . DIRECTORY_SEPARATOR . 'block-1.html';
//		$parser = new Parser($template);
//		$data = $parser->process();
//	}

	/**
	 * @covers ::replaceIncludes
	 * @covers ::replaceInclude
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::__construct
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::process
	 * @uses \Kshabazz\Web\SigmaRemix\Parser::setPlaceholders
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