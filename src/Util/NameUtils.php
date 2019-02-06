<?php
/**
 * Utility functions to retrieve information about parameters passed to function calls,
 * array declarations, list, isset and unset constructs.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2006-2018 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Util;

use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

class NameUtils
{
	
	/**
	 * Regular expression to check if a given structure name is valid for use in PHP.
	 *
	 * @var string
	 *
	 * @link http://php.net/manual/en/language.variables.basics.php
	 * @link http://php.net/manual/en/language.constants.php
	 * @link http://php.net/manual/en/functions.user-defined.php
	 * @link http://php.net/manual/en/language.oop5.basic.php
	 */
	const PHP_LABEL_REGEX = '`^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$`';


    /**
     * Returns the declaration names for classes, interfaces, traits, and functions.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the declaration token which
     *                                               declared the class, interface, trait, or function.
     *
     * @return string|null The name of the class, interface, trait, or function;
     *                     or NULL if the function or class is anonymous.
     * @throws \PHP_CodeSniffer\Exceptions\RuntimeException If the specified token is not of type
     *                                                      T_FUNCTION, T_CLASS, T_TRAIT, or T_INTERFACE.
     */
    public static function getDeclarationName(File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();
        $tokenCode = $tokens[$stackPtr]['code'];

        if ($tokenCode === T_ANON_CLASS || $tokenCode === T_CLOSURE) {
            return null;
        }

        if ($tokenCode !== T_FUNCTION
            && $tokenCode !== T_CLASS
            && $tokenCode !== T_INTERFACE
            && $tokenCode !== T_TRAIT
        ) {
            throw new RuntimeException('Token type "'.$tokens[$stackPtr]['type'].'" is not T_FUNCTION, T_CLASS, T_INTERFACE or T_TRAIT');
        }

        if ($tokenCode === T_FUNCTION
            && strtolower($tokens[$stackPtr]['content']) !== 'function'
        ) {
            // This is a function declared without the "function" keyword.
            // So this token is the function name.
            return $tokens[$stackPtr]['content'];
        }

        $nextNonEmpty = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($nextNonEmpty !== false && $tokens[$nextNonEmpty]['code'] === T_STRING) {
            return $tokens[$nextNonEmpty]['content'];
        }

        return null;

    }//end getDeclarationName()


    /**
     * Returns true if the specified string is in the camel caps format.
     *
     * @param string  $string      The string the verify.
     * @param boolean $classFormat If true, check to see if the string is in the
     *                             class format. Class format strings must start
     *                             with a capital letter and contain no
     *                             underscores.
     * @param boolean $public      If true, the first character in the string
     *                             must be an a-z character. If false, the
     *                             character must be an underscore. This
     *                             argument is only applicable if $classFormat
     *                             is false.
     * @param boolean $strict      If true, the string must not have two capital
     *                             letters next to each other. If false, a
     *                             relaxed camel caps policy is used to allow
     *                             for acronyms.
     *
     * @return boolean
     */
    public static function isCamelCaps(
        $string,
        $classFormat=false,
        $public=true,
        $strict=true
    ) {
        // Check the first character first.
        if ($classFormat === false) {
            $legalFirstChar = '';
            if ($public === false) {
                $legalFirstChar = '[_]';
            }

            if ($strict === false) {
                // Can either start with a lowercase letter, or multiple uppercase
                // in a row, representing an acronym.
                $legalFirstChar .= '([A-Z]{2,}|[a-z])';
            } else {
                $legalFirstChar .= '[a-z]';
            }
        } else {
            $legalFirstChar = '[A-Z]';
        }

        if (preg_match("/^$legalFirstChar/", $string) === 0) {
            return false;
        }

        // Check that the name only contains legal characters.
        $legalChars = 'a-zA-Z0-9';
        if (preg_match("|[^$legalChars]|", substr($string, 1)) > 0) {
            return false;
        }

        if ($strict === true) {
            // Check that there are not two capital letters next to each other.
            $length          = strlen($string);
            $lastCharWasCaps = $classFormat;

            for ($i = 1; $i < $length; $i++) {
                $ascii = ord($string{$i});
                if ($ascii >= 48 && $ascii <= 57) {
                    // The character is a number, so it cant be a capital.
                    $isCaps = false;
                } else {
                    if (strtoupper($string{$i}) === $string{$i}) {
                        $isCaps = true;
                    } else {
                        $isCaps = false;
                    }
                }

                if ($isCaps === true && $lastCharWasCaps === true) {
                    return false;
                }

                $lastCharWasCaps = $isCaps;
            }
        }//end if

        return true;

    }//end isCamelCaps()


    /**
     * Returns true if the specified string is in the underscore caps format.
     *
     * @param string $string The string to verify.
     *
     * @return boolean
     */
    public static function isUnderscoreName($string)
    {
        // If there is a space in the name, it can't be valid.
        if (strpos($string, ' ') !== false) {
            return false;
        }

        $validName = true;
        $nameBits  = explode('_', $string);

        if (preg_match('|^[A-Z]|', $string) === 0) {
            // Name does not begin with a capital letter.
            $validName = false;
        } else {
            foreach ($nameBits as $bit) {
                if ($bit === '') {
                    continue;
                }

                if ($bit{0} !== strtoupper($bit{0})) {
                    $validName = false;
                    break;
                }
            }
        }

        return $validName;

    }//end isUnderscoreName()








/*
 * - PascalCase = the first letter of every word in the identifier is upper case
 *                (called Proper case), the rest lower case and merged without space.
 *                Two consequtive capitals allowed.
 *                Also called StudlyCaps or UpperCamelCase
 *
 * - camelCase  = the first letter of the first word in the identifier is lower case,
 *                and all subsequent words use proper case.
 * - WikiCase   = Initial letters capitalized and words run together (like PascalCase),
 *                but each capital letter must be followed by a lower case letter
 *                (hence one-letter words are not accommodated).
 * - snake_case = All letters lowercase, words separated by underscores.
 * - MACRO_CASE = UpperCase with underscores
 *                All letters capitalized, words separated by underscores.
 * - StudlyCaps = Same as UpperCamelCase, but may be carried out in a more random fashion,
 *                or to its extreme. (alternating every letter). Originates from bulletin-board
 *                systems, where it was used along with numeric or symbolic substitution (l1|<3 th15)
 *                and other devices to convey apparent coolness. Now generally used only facetiously.
 *                tHiS iS aN eXaMpLe.
 * - kebab-case = All letters lowercase, words separated by hyphen.
 * - COBOL-CASE = All letters uppercase, words separated by hyphen.
 *
 *
 * Need to know for each:
 * - consecutive capitals allowed ? limited to a max number of subsequent capitals ?
 * - numbers allowed ?
 * - underscores allowed ?
 * - consecutive underscores allowed ?
 * - space allowed ?
 * - hyphen allowed ? (CSS)
 */


    // This function should always be used by naming sniffs to check that the suggested
    // alternative name is valid PHP!
    // The is/to naming methods can also be used for JS/CSS and other syntax, so those
    // don't take this into account.

    public static function isValidPHPName($name)
	{
        return (preg_match(self::PHP_LABEL_REGEX, $name) === 1);
	}
	
	public static function isValidJSName($name)
	{
	}

	public static function isValidCSSSelectorName($name)
	{
	}


    public static function toCamelCase($name)
    {
    }

    public static function isCamelCase($name)
    {
    }

    public static function toPascalCase($name)
    {
    }

    public static function isPascalCase($name)
    {
    }


    public static function toCamelCaps($name)
    {
    }
/*
    public static function isCamelCaps($name)
    {
    }
*/

    /**
     * Return whether the variable is in snake_case.
     *
     * @param string $name The construct name to examine.
     *
     * @return bool
     */
    public static function isSnakeCase($name) {
        return (bool) preg_match('`^[a-z0-9]+(?:_[a-z0-9]+)*$`', $name);
    }

    /**
     * Transform the name of a PHP construct (function, variable etc) to one in snake_case.
     *
     * @param string $name The construct name to transform.
     *
     * @return string
     */
    public static function toSnakeCase($name)
    {
        $name = str_replace('-', '_', $name);
        $name = preg_replace('`([A-Z])`', '_$1', $name);
        $name = strtolower($name);
        $name = str_replace('__', '_', $name);
        $name = trim($name, '_');

        return $name;

    }

	/**
	 *
     * @param string  $name      The string the verify.
     * @param boolean $strict      If true, the string must not have two capital
     *                             letters next to each other.
     *                             If false, acronyms are allowed.
     *                             Defaults to false.
     *
     * @return bool
	 */
    public static function isUpperSnakeCase($name, $strict=false)
    {
		if ($strict === false) {
			return (preg_match('`^[A-Z][A-Za-z0-9]*(?:_[A-Z][A-Za-z0-9]*)*$`', $name) === 1);
		}
		
        return (preg_match('`^[A-Z][a-z0-9]*(?:_[A-Z][a-z0-9]*)*$`', $name) === 1);
    }

    public static function toUpperSnakeCase($name)
    {
    }



	/**
	 *
	 * MACRO_CASE = Uppercase with words separated with underscores.
	 *
	 */
    public static function isMacroCase($name)
    {
        return (preg_match('`^[A-Z0-9]+(?:_[A-Z0-9]+)*$`', $name) === 1);
    }

    public static function toMacroCase($name)
    {
    }



    /**
     * Return whether the name, like a CSS selector, is in kebab-case.
     *
     * @param string $name The name to examine.
     *
     * @return bool
     */
    public static function isKebabCase($name) {
        return (preg_match('`^[a-z0-9]+(?:-[a-z0-9]+)*$`', $name) === 1);
    }

    /**
     * Transform a name to one in kebab-case.
     *
     * @param string $name The name to transform.
     *
     * @return string Transformed name or an empty string if no reliable transformation
     *                could be executed.
     */
    public static function toKebabCase($name)
    {
        $name = str_replace('_', '-', $name);
        $name = preg_replace('`([A-Z])`', '-$1', $name);
        $name = strtolower($name);
        $name = str_replace('--', '-', $name);
        $name = trim($name, '-');
        
        if (self::isKebabCase($name) === true) {
			return $name;
		}

        return '';

    }

	/**
	 *
     * @param string  $name      The string the verify.
     * @param boolean $strict      If true, the string must not have two capital
     *                             letters next to each other.
     *                             If false, acronyms are allowed.
     *                             Defaults to false.
     *
     * @return bool
	 */
    public static function isTrainCase($name, $strict=false)
    {
		if ($strict === false) {
			return (preg_match('`^[A-Z][A-Za-z0-9]*(?:-[A-Z][A-Za-z0-9]*)*$`', $name) === 1);
		}
		
        return (preg_match('`^[A-Z][a-z0-9]*(?:-[A-Z][a-z0-9]*)*$`', $name) === 1);
    }

    public static function toTrainCase($name)
    {
    }



	/**
	 *
	 * MACRO_CASE = Uppercase with words separated with underscores.
	 *
	 */
    public static function isCobolCase($name)
    {
        return (preg_match('`^[A-Z0-9]+(?:-[A-Z0-9]+)*$`', $name) === 1);
    }

    public static function toCobolCase($name)
    {
    }



	// Transform helper functions
	public static function ltrimNumbers($name)
	{
		return ltrim($name, '0123456789');
	}
	
	public static function lowerConsecutiveCaps($name)
	{
		// Needs testing!
		$name = preg_replace_callback(
			'`([A-Z])([A-Z]+)([A-Z]|\b|$)`',
			function($matches) {
				return $matches[1].strtolower($matches[2]).$matches[3];
            },
			$name
		);

		return $name;
	}

}//end class
