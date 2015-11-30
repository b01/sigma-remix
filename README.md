# Description
A rewrite of the PEAR module HTML_Template_Sigma written by Alexey Borzov and others.

## Why A Re-Write
I like how well Sigma integrates with HTML and the API is quite simple. However, its written as one big class, and can
be hard to modify; although I do not plan to modify it much. I finally decided to rewrite it to modernize it a bit and
see if I can squeeze a bit more performance out of it. The most compelling reason, which I finally gave into doing a
re-write wsa so that I can output the compiled templates as PHTML. Which should allow any PHP developer to easily
review/debug the compiled template.

## Example of Template Compilation

### Template Input:
```html
<!-- COMMENT -->templates/some.html <!-- /COMMENT -->

<!-- BEGIN TEST_BLOCK_1 -->
	Test content.

	<!-- INCLUDE templates/some.html -->

	<!-- BEGIN TEST_BLOCK_2 -->
		<p>Some more test content with a {placeholder_1}.</p>
	<!-- END TEST_BLOCK_2 -->

	<!-- BEGIN TEST_BLOCK_3 -->
		Another {placeholder_2}, but this time we also add in a func_upper_case('function')

		<!-- BEGIN TEST_BLOCK_4 -->
			<p>Repeat a {placeholder_1}</p>
			<p> Add a function using a placeholder as input func_upper_case({placeholder_2})

		<!-- END TEST_BLOCK_4 -->

	<!-- END TEST_BLOCK_3 -->

	<ul>
	<!-- BEGIN TEST_BLOCK_5 -->
		<li>Item {$itemNo}</>
	<!-- END TEST_BLOCK_5 -->
	</ul>
<!-- END TEST_BLOCK_1 -->
```

### PHP Output:
```php
<?php
extract([
	'placeholder_1_ph' => 'replacement',
	'placeholder_2_ph' => 'placeholder_2',
	'itemNo' => '3'
	'itemNo' => 'cherry'
]);

<?php $TEST_BLOCK_1_ary = [[]];
foreach ($TEST_BLOCK_1_ary as $TEST_BLOCK_1_val ):
	extract($TEST_BLOCK_1_val); // BEGIN TEST_BLOCK_1 ?>
	Test content.

	<!-- embedded contents of templates/some.html -->
	<p>This is the content of templates/some.html, which was embedded in this template.</p>

	<?php $TEST_BLOCK_2_ary = [[]];
		foreach ($TEST_BLOCK_2_ary as $TEST_BLOCK_2_val):
		extract($TEST_BLOCK_1_val); // TEST_BLOCK_2 ?>
		<p>Some more test content with a <?= $placeholder_1_ph ?>.</p>
	<?php endforeach; // END TEST_BLOCK_2 ?>

	<?php $TEST_BLOCK_3_ary = [[]];
	foreach ($TEST_BLOCK_3_ary as $TEST_BLOCK_3_val):
	extract($TEST_BLOCK_3_val); // TEST_BLOCK_3 -->
		Another {placeholder_2}, but this time we also add in a func_upper_case('function')

		<?php $TEST_BLOCK_4_ary = [[]]; foreach ($TEST_BLOCK_4_ary as $TEST_BLOCK_4_val):
		 	extract($TEST_BLOCK_4_val); ?>
			<p>Repeat a <?= placeholder_1 ?></p>
			<p> Add a function using a placeholder as input func_upper_case(<?= $placeholder_2_ph ?>)
		<!-- END TEST_BLOCK_4 -->

	<?php endforeach; // END TEST_BLOCK_3 ?>

	<ul>
	<?php
	// The inner array(s) will be extracted as variables, overwriting any variables already defined globally.
	$TEST_BLOCK_5_ary = [[itemNo => 1], [itemNo => 2], [itemNo => 3]];
		foreach ($TEST_BLOCK_5_ary as $TEST_BLOCK_5_val):
		extract($TEST_BLOCK_5_val); ?>
		<li>Item {$itemNo} {$item:func_capitalize}</li>
	<?php endforeach; //END TEST_BLOCK_5 ?>
	</ul>
<?php endforeach; // END TEST_BLOCK_1 ?>
?>

PHP View Code:
```php
/Views/Some.php
class Some
{
	public function render()
	{
		$template = new Template( 'templates/some.html' );

		// Loop through TEST_BLOCK_5
		$items = ['apple', 'banana', 'cherry'];
		foreach ( $items as $itemNo => $item ) {
			$template->parseBlock(
				'TEST_BLOCK_5',
				['itemNo' => $itemNo, 'item' => $item]
			);
		}
	}
}
```

Rendered Output:
```html

<!-- TEST_BLOCK_5 Output: -->
<ul>
	<li>Item 1 Apple</li>
	<li>Item 2 Banana</li>
	<li>Item 3 Cherry</li>
</ul>
```