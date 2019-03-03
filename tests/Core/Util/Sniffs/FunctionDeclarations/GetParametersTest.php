<?php
/**
 * Tests for the \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters() method.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2019 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Tests\Core\Util\Sniffs\FunctionDeclarations;

use PHP_CodeSniffer\Tests\Core\AbstractMethodUnitTest;
use PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations;

class GetParametersTest extends AbstractMethodUnitTest
{


    /**
     * Test receiving an expected exception when a non function token is passed.
     *
     * @expectedException        PHP_CodeSniffer\Exceptions\RuntimeException
     * @expectedExceptionMessage $stackPtr must be of type T_FUNCTION or T_CLOSURE
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testNotAFunctionException()
    {
        $interface = $this->getTargetToken('/* testNotAFunction */', T_INTERFACE);
        $result    = FunctionDeclarations::getParameters(self::$phpcsFile, $interface);

    }//end testNotAFunctionException()


    /**
     * Verify function declaration without parameters.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testFunctionNoParams()
    {
        $expected = [];
        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testFunctionNoParams()


    /**
     * Verify pass-by-reference parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testPassByReference()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => '&$var',
            'pass_by_reference' => true,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testPassByReference()


    /**
     * Verify array hint parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testArrayHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => 'array $var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => 'array',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testArrayHint()


    /**
     * Verify type hint parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testTypeHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var1',
            'content'           => 'foo $var1',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => 'foo',
            'nullable_type'     => false,
        ];

        $expected[1] = [
            'name'              => '$var2',
            'content'           => 'bar $var2',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => 'bar',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testTypeHint()


    /**
     * Verify self type hint parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testSelfTypeHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => 'self $var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => 'self',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testSelfTypeHint()


    /**
     * Verify callable type hint parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testCallableTypeHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => 'callable $var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => 'callable',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testCallableTypeHint()


    /**
     * Verify nullable callable type hint parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testNullableCallableTypeHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => '?callable $var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '?callable',
            'nullable_type'     => true,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testNullableCallableTypeHint()


    /**
     * Verify nullable type hint parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testNullableTypeHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var1',
            'content'           => '?int $var1',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '?int',
            'nullable_type'     => true,
        ];

        $expected[1] = [
            'name'              => '$var2',
            'content'           => '?\bar $var2',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '?\bar',
            'nullable_type'     => true,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testNullableTypeHint()


    /**
     * Verify iterable type hint parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testIterableTypeHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => 'iterable $var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => 'iterable',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testIterableTypeHint()


    /**
     * Verify variable.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testVariable()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => '$var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testVariable()


    /**
     * Verify default value parsing with a single function param.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testSingleDefaultValue()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var1',
            'content'           => '$var1=self::CONSTANT',
            'default'           => 'self::CONSTANT',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testSingleDefaultValue()


    /**
     * Verify default value parsing.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testDefaultValues()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var1',
            'content'           => '$var1=1',
            'default'           => '1',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];
        $expected[1] = [
            'name'              => '$var2',
            'content'           => "\$var2='value'",
            'default'           => "'value'",
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testDefaultValues()


    /**
     * Verify default value parsing with array values.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testArrayDefaultValues()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var1',
            'content'           => '$var1 = []',
            'default'           => '[]',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];
        $expected[1] = [
            'name'              => '$var2',
            'content'           => '$var2 = array(1, 2, 3)',
            'default'           => 'array(1, 2, 3)',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testArrayDefaultValues()


    /**
     * Verify having a T_STRING constant as a default value for the second parameter.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testConstantDefaultValueSecondParam()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var1',
            'content'           => '$var1',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];
        $expected[1] = [
            'name'              => '$var2',
            'content'           => '$var2 = M_PI',
            'default'           => 'M_PI',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testConstantDefaultValueSecondParam()


    /**
     * Verify using ellipsis with a typehint.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testVariableLengthArgument()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$unit',
            'content'           => '$unit',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];
        $expected[1] = [
            'name'              => '$intervals',
            'content'           => 'DateInterval ...$intervals',
            'pass_by_reference' => false,
            'variable_length'   => true,
            'type_hint'         => 'DateInterval',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testVariableLengthArgument()


    /**
     * Verify "bitwise and" in default value !== pass-by-reference.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testBitwiseAndConstantExpressionDefaultValue()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$a',
            'content'           => '$a = 10 & 20',
            'default'           => '10 & 20',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testBitwiseAndConstantExpressionDefaultValue()


    /**
     * Verify a fully qualified class name being set as type declaration.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testFQCNTypeHint()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => '\MyNS\SubCat\MyClass $var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '\MyNS\SubCat\MyClass',
            'nullable_type'     => false,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testFQCNTypeHint()


    /**
     * Verify a fully qualified class name being set as type declaration interlaced
     * with whitespace and comments.
     *
     * @covers \PHP_CodeSniffer\Util\Sniffs\FunctionDeclarations::getParameters
     *
     * @return void
     */
    public function testFQCNTypeHintWithCommentsAndWhiteSpace()
    {
        $expected    = [];
        $expected[0] = [
            'name'              => '$var',
            'content'           => '?\MyNS /* comment */
        \SubCat // phpcs:ignore Standard.Cat.Sniff -- for reasons.
            \MyClass $var',
            'pass_by_reference' => false,
            'variable_length'   => false,
            'type_hint'         => '?\MyNS\SubCat\MyClass',
            'nullable_type'     => true,
        ];

        $this->getParametersTestHelper('/* '.__FUNCTION__.' */', $expected);

    }//end testFQCNTypeHintWithCommentsAndWhiteSpace()


    /**
     * Test helper.
     *
     * @param string $testMarker The comment which prefaces the target token in the test file.
     * @param array  $expected   The expected function output.
     *
     * @return void
     */
    private function getParametersTestHelper($testMarker, $expected)
    {
        $function = $this->getTargetToken($testMarker, [T_FUNCTION]);
        $found    = FunctionDeclarations::getParameters(self::$phpcsFile, $function);

        foreach ($found as $key => $value) {
            unset($found[$key]['token'], $found[$key]['type_hint_token']);
        }

        $this->assertSame($expected, $found);

    }//end getParametersTestHelper()


}//end class