NOTES FOR THE METHODS:

https://github.com/outsideris/popularconvention/blob/master/src/parser/php-parser.coffee#L480 and down for regexes.

* Transform methods should do cross-check at end to make sure they return something valid.
* If not return empty string.



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

Method 1:
$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
substr(str_shuffle($permitted_chars), 0, 16);

This method of generating random alphanumeric strings is very easy, but it has a couple of issues. For example, you will never get the same characters in your random string twice. Also, the length of the random output string can only be as long as the input string.

Method 2:
$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

function generate_string($input, $strength = 16) {
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }

    return $random_string;
}

=> combine this with rand($min, $max) for length ;-)
 
// Output: iNCHNGzByPjhApvn7XBD
echo generate_string($permitted_chars, 20);
 
// Output: 70Fmr9mOlGID7OhtTbyj
echo generate_string($permitted_chars, 20);
 
// Output: Jp8iVNhZXhUdSlPi1sMNF7hOfmEWYl2UIMO9YqA4faJmS52iXdtlA3YyCfSlAbLYzjr0mzCWWQ7M8AgqDn2aumHoamsUtjZNhBfU
echo generate_string($permitted_chars, 100);

Method 3:
https://gist.github.com/irazasyed/5382685
(basically the same as method 2)

Method 4 - variation of the same
/*
 * Create a random string
 * @author	XEWeb <>
 * @param $length the length of the string to create
 * @return $str the string
 */
function randomString($length = 6) {
	$str = "";
	$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
	$max = count($characters) - 1;
	for ($i = 0; $i < $length; $i++) {
		$rand = mt_rand(0, $max);
		$str .= $characters[$rand];
	}
	return $str;
}

For the random test, use below in combination with a Travis specific ENV variable to skip it on travis:
https://docs.travis-ci.com/user/environment-variables/#default-environment-variables
    $travis = getenv('TRAVIS');
    if (getenv('TRAVIS') === true) {
        $this->markTestSkipped(
           'Test skipped on Travis.'
        );
	}




Tests are also needed for all types with:
* numbers within the string
* double underscores
* double hyphens
* Acronyms (consecutive caps)
* Starting with underscore
* Ending with underscore
* Starting with hyphen
* Ending with hyphen
* Starting with number
* Ending with number
* Tests with one or more of the \x7f-\xff characters !!! Also make sure that the is methods can deal with these!



// Both methods use same dataprovider ?

function testIsSnakeCase($name, $expectedIs, $notUsed) {
	$this->assertSame($expected, NameUtils::isSnakeCase($name));
}

function testToSnakeCase($name, $notUsed, $expectedTransform=null) {
	$this->assertSame($expectedTransform, NameUtils::toSnakeCase($name));
}


public static function dataIsSnakeCase() {
	return self::dataProviderHelperIs('snakecase');
}

public static function dataToSnakeCase() {
	return self::dataProviderHelperTo('snakecase');
}


protected static function dataProviderHelperIs($standard) {
	$data = [];
	foreach (self::$names as $info) {
		$dataset = [$info['name']];

		if (isset($info['valid'][$standard]) === true) {
			$dataset[] = true;
		} else {
			$dataset[] = false;
		}

		$data[$info['name']] = $dataset;
	}
	
	return $data;
}

protected static function dataProviderHelperTo($standard) {
	$data = [];
	foreach (self::$names as $info) {
		$valid = false;
		if (isset($info['valid'][$standard]) === true) {
			$valid = true;
		}

		if ($valid === false) {
  		    $data[$info['name']] = [
			    $info['name'],
			    $info['transform'][$standard],
			];
		}
	}
	
	return $data;
}