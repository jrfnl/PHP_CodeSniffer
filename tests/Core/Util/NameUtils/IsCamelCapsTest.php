<?php
/**
 * Tests for the \PHP_CodeSniffer\Util\NameUtils::isCamelCaps method.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Tests\Core\Util\NameUtils;

use PHP_CodeSniffer\Util\NameUtils;
use PHPUnit\Framework\TestCase;

class IsCamelCapsTest extends TestCase
{


    /**
     * Test valid public function/method names.
     *
     * @return void
     */
    public function testValidNotClassFormatPublic()
    {
        $this->assertTrue(NameUtils::isCamelCaps('thisIsCamelCaps', false, true, true));
        $this->assertTrue(NameUtils::isCamelCaps('thisISCamelCaps', false, true, false));

    }//end testValidNotClassFormatPublic()


    /**
     * Test invalid public function/method names.
     *
     * @return void
     */
    public function testInvalidNotClassFormatPublic()
    {
        $this->assertFalse(NameUtils::isCamelCaps('_thisIsCamelCaps', false, true, true));
        $this->assertFalse(NameUtils::isCamelCaps('thisISCamelCaps', false, true, true));
        $this->assertFalse(NameUtils::isCamelCaps('ThisIsCamelCaps', false, true, true));

        $this->assertFalse(NameUtils::isCamelCaps('3thisIsCamelCaps', false, true, true));
        $this->assertFalse(NameUtils::isCamelCaps('*thisIsCamelCaps', false, true, true));
        $this->assertFalse(NameUtils::isCamelCaps('-thisIsCamelCaps', false, true, true));

        $this->assertFalse(NameUtils::isCamelCaps('this*IsCamelCaps', false, true, true));
        $this->assertFalse(NameUtils::isCamelCaps('this-IsCamelCaps', false, true, true));
        $this->assertFalse(NameUtils::isCamelCaps('this_IsCamelCaps', false, true, true));
        $this->assertFalse(NameUtils::isCamelCaps('this_is_camel_caps', false, true, true));

    }//end testInvalidNotClassFormatPublic()


    /**
     * Test valid private method names.
     *
     * @return void
     */
    public function testValidNotClassFormatPrivate()
    {
        $this->assertTrue(NameUtils::isCamelCaps('_thisIsCamelCaps', false, false, true));
        $this->assertTrue(NameUtils::isCamelCaps('_thisISCamelCaps', false, false, false));
        $this->assertTrue(NameUtils::isCamelCaps('_i18N', false, false, true));
        $this->assertTrue(NameUtils::isCamelCaps('_i18n', false, false, true));

    }//end testValidNotClassFormatPrivate()


    /**
     * Test invalid private method names.
     *
     * @return void
     */
    public function testInvalidNotClassFormatPrivate()
    {
        $this->assertFalse(NameUtils::isCamelCaps('thisIsCamelCaps', false, false, true));
        $this->assertFalse(NameUtils::isCamelCaps('_thisISCamelCaps', false, false, true));
        $this->assertFalse(NameUtils::isCamelCaps('_ThisIsCamelCaps', false, false, true));
        $this->assertFalse(NameUtils::isCamelCaps('__thisIsCamelCaps', false, false, true));
        $this->assertFalse(NameUtils::isCamelCaps('__thisISCamelCaps', false, false, false));

        $this->assertFalse(NameUtils::isCamelCaps('3thisIsCamelCaps', false, false, true));
        $this->assertFalse(NameUtils::isCamelCaps('*thisIsCamelCaps', false, false, true));
        $this->assertFalse(NameUtils::isCamelCaps('-thisIsCamelCaps', false, false, true));
        $this->assertFalse(NameUtils::isCamelCaps('_this_is_camel_caps', false, false, true));

    }//end testInvalidNotClassFormatPrivate()


    /**
     * Test valid class names.
     *
     * @return void
     */
    public function testValidClassFormatPublic()
    {
        $this->assertTrue(NameUtils::isCamelCaps('ThisIsCamelCaps', true, true, true));
        $this->assertTrue(NameUtils::isCamelCaps('ThisISCamelCaps', true, true, false));
        $this->assertTrue(NameUtils::isCamelCaps('This3IsCamelCaps', true, true, false));

    }//end testValidClassFormatPublic()


    /**
     * Test invalid class names.
     *
     * @return void
     */
    public function testInvalidClassFormat()
    {
        $this->assertFalse(NameUtils::isCamelCaps('thisIsCamelCaps', true));
        $this->assertFalse(NameUtils::isCamelCaps('This-IsCamelCaps', true));
        $this->assertFalse(NameUtils::isCamelCaps('This_Is_Camel_Caps', true));

    }//end testInvalidClassFormat()


    /**
     * Test invalid class names with the private flag set.
     *
     * Note that the private flag is ignored if the class format
     * flag is set, so these names are all invalid.
     *
     * @return void
     */
    public function testInvalidClassFormatPrivate()
    {
        $this->assertFalse(NameUtils::isCamelCaps('_ThisIsCamelCaps', true, true));
        $this->assertFalse(NameUtils::isCamelCaps('_ThisIsCamelCaps', true, false));

    }//end testInvalidClassFormatPrivate()


}//end class
