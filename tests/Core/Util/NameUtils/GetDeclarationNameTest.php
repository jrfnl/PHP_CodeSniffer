<?php
/**
 * Tests for the \PHP_CodeSniffer\Util\NameUtils::getDeclarationName method.
 *
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2018 Juliette Reinders Folmer. All rights reserved.
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Tests\Core\Util\NameUtils;

use PHP_CodeSniffer\Tests\Core\AbstractMethodUnitTest;
use PHP_CodeSniffer\Util\NameUtils;

class GetDeclarationNameTest extends AbstractMethodUnitTest
{


    /**
     * Test receiving an expected exception when a non-supported token is passed.
     *
     * @expectedException        PHP_CodeSniffer\Exceptions\RuntimeException
     * @expectedExceptionMessage Token type "T_ECHO" is not T_FUNCTION, T_CLASS, T_INTERFACE or T_TRAIT
     *
     * @covers \PHP_CodeSniffer\Util\NameUtils::getDeclarationName
     *
     * @return void
     */
    public function testInvalidTokenPassed()
    {
        $interface = $this->getTargetToken('/* testInvalidTokenPassed */', T_ECHO);
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
            [
                '/* testAnonClass */',
                T_ANON_CLASS,
            ],
            [
                '/* testMissingInterfaceName */',
                T_INTERFACE,
            ],
            [
                '/* testLiveCoding */',
                T_CLASS,
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
            [
                '/* testAbstractMethod */',
                'abstractMethodName',
            ],
            [
                '/* testExtendedClass */',
                'ExtendedClass',
            ],
            [
                '/* testInterface */',
                'InterfaceName',
            ],
            [
                '/* testTrait */',
                'TraitName',
            ],
            [
                '/* testClassWithCommentsAndNewLines */',
                'ClassWithCommentsAndNewLines',
            ],
        ];

    }//end dataGetDeclarationName()


}//end class
