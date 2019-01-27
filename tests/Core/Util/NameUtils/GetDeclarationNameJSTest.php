<?php
/**
 * Tests for the \PHP_CodeSniffer\Util\NameUtils::getDeclarationName method.
 *
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2018 Juliette Reinders Folmer. All rights reserved.
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Tests\Core\Util\NameUtils;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Tests\Core\AbstractMethodUnitTest;
use PHP_CodeSniffer\Util\NameUtils;

class GetDeclarationNameJSTest extends AbstractMethodUnitTest
{


    /**
     * Initialize & tokenize \PHP_CodeSniffer\Files\File with code from the test case file.
     *
     * This overloads the setUpBeforeClass() method from the abstract class to allow for
     * a JS test case file.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        $config = new Config();

        // Set to PEAR rather than Generic to prevent issues with missing ESLint config settings.
        $config->standards = ['PEAR'];

        $ruleset = new Ruleset($config);

        $pathToTestFile = realpath(__DIR__).DIRECTORY_SEPARATOR.'GetDeclarationNameJSTest.js';

        // Make sure the file gets parsed as JS.
        $contents  = 'phpcs_input_file: '.$pathToTestFile.PHP_EOL;
        $contents .= file_get_contents($pathToTestFile);

        self::$phpcsFile = new DummyFile($contents, $ruleset, $config);
        self::$phpcsFile->process();

    }//end setUpBeforeClass()


    /**
     * Test receiving an expected exception when a non-supported token is passed.
     *
     * @expectedException        PHP_CodeSniffer\Exceptions\RuntimeException
     * @expectedExceptionMessage Token type "T_STRING" is not T_FUNCTION, T_CLASS, T_INTERFACE or T_TRAIT
     *
     * @covers \PHP_CodeSniffer\Util\NameUtils::getDeclarationName
     *
     * @return void
     */
    public function testInvalidTokenPassed()
    {
        $interface = $this->getTargetToken('/* testInvalidTokenPassed */', T_STRING);
        $result    = NameUtils::getDeclarationName(self::$phpcsFile, $interface);

    }//end testInvalidTokenPassed()


    /**
     * Test the getDeclarationName() method for expected "null" output.
     *
     * @param string     $identifier Comment which preceeds the test case.
     * @param int|string $targetType Token type of the token to get as stackPtr.
     *
     * @dataProvider dataGetDeclarationNameNull
     * @covers       \PHP_CodeSniffer\Util\NameUtils::getDeclarationName
     *
     * @return void
     */
    public function testGetDeclarationNameNull($identifier, $targetType)
    {
        $target = $this->getTargetToken($identifier, $targetType);
        $result = NameUtils::getDeclarationName(self::$phpcsFile, $target);
        $this->assertNull($result);

    }//end testGetDeclarationNameNull()


    /**
     * Data provider for the GetDeclarationNameNull test.
     *
     * @see testGetDeclarationNameNull()
     *
     * @return array
     */
    public function dataGetDeclarationNameNull()
    {
        return [
            [
                '/* testClosure */',
                T_CLOSURE,
            ],
        ];

    }//end dataGetDeclarationNameNull()


    /**
     * Test the getDeclarationName() method.
     *
     * @param string $identifier Comment which preceeds the test case.
     * @param array  $expected   Expected function output.
     *
     * @dataProvider dataGetDeclarationName
     * @covers       \PHP_CodeSniffer\Util\NameUtils::getDeclarationName
     *
     * @return void
     */
    public function testGetDeclarationName($identifier, $expected)
    {
        $target = $this->getTargetToken($identifier, [T_CLASS, T_INTERFACE, T_TRAIT, T_FUNCTION]);
        $result = NameUtils::getDeclarationName(self::$phpcsFile, $target);
        $this->assertSame($expected, $result);

    }//end testGetDeclarationName()


    /**
     * Data provider for the GetDeclarationName test.
     *
     * @see testGetDeclarationName()
     *
     * @return array
     */
    public function dataGetDeclarationName()
    {
        return [
            [
                '/* testFunction */',
                'functionName',
            ],
            [
                '/* testClass */',
                'ClassName',
            ],
            [
                '/* testMethod */',
                'methodName',
            ],
        ];

    }//end dataGetDeclarationName()


}//end class
