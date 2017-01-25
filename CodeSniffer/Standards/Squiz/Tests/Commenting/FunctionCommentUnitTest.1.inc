<?php
/**
 * Issue #1081.
 */

/**
 * Return description function + mixed return types.
 *
 * @return bool|int This is a description.
 */
function returnTypeWithDescriptionA()
{
    return 5;

}//end returnTypeWithDescriptionA()


/**
 * Return description function + mixed return types.
 *
 * @return real|bool This is a description.
 */
function returnTypeWithDescriptionB()
{
    return 5;

}//end returnTypeWithDescriptionB()


/**
 * Return description function + lots of different mixed return types.
 *
 * @return int|object|string[]|real|double|float|bool|array(int=>MyClass)|callable And here we have a description
 */
function returnTypeWithDescriptionC()
{
    return 5;

}//end returnTypeWithDescriptionC()


/**
 * Return description function + lots of different mixed return types.
 *
 * @return array(int=>bool)|\OtherVendor\Package\SomeClass2|MyClass[]|void And here we have a description
 */
function returnTypeWithDescriptionD()
{

}//end returnTypeWithDescriptionD()


/**
 * Issue disappearing param variable types when fixing.
 */


/**
 * Mixed variable type separated by a '|'.
 *
 * @param string|array() $a1 Comment here.
 * @param double|int     $a2 Comment here.
 * @param MyClass|real   $a3 Comment here.
 * @param MyClass|int    $a4 Comment here.
 * @param bool|int       $a5 Comment here.
 *
 * @return boolean
 */
function mixedType($a1, $a2, $a3, $a4, $a5)
{
    return true;

}//end mixedType()
