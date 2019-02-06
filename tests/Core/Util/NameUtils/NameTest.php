NOTES FOR UNIT TESTS:

Set up a large set of names with info about them
[
   'name' => 'name',
   'valid' => [
       'standard it complies with' => true,
       'standard it complies with' => true,
   ],
   'transform' => [
	   'camelcase' => '',
	   'pascalcase' => '',
	   'wikicase' => '',
	   'snakecase' => '',
	   'uppersnakecase' => '',
	   'macrocase' => '',
	   'kebabcase' => '',
	   'traincase' => '',
	   'cobolcase' => '',
	],
]


Set up data providers which pick & create data sets based on the above


Figure something out about setting additional options, like $strict

See above setting up something generating random strings and doing the "to" and after transform "is" tests for those.


function testSnakeCase($name, $expectedIs, $expectedTransform=null) {
	$this->assertSame($expected, NameUtils::isSnakeCase($name), 'Name check failed');

	if ($expectedIs === false) {
		$transformed = NameUtils::toSnakeCase($name);
		$this->assertSame($expectedTransform, $transformed, 'Transformation test failed');
		$this->assertTrue(NameUtils::isSnakeCase($transformed), 'Transformation does not comply with format');
	}
}

function dataSnakeCase() {
	return self::dataProviderHelper('snakecase');
}


protected static function dataProviderHelper($standard) {
	$data = [];
	foreach (self::$names as $info) {
		$dataset = [$info['name']];
		
		$valid = false;
		if (isset($info['valid'][$standard]) === true) {
			$valid = true;
		}

		$dataset[] = $valid;

		if ($valid === false) {
			$dataset[] = $info['transform'][$standard];
		}
		
		$data[] = $dataset;
	}
	
	return $data;
}