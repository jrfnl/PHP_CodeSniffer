<?php
/**
 * Basic util functions.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Util;

class Common
{

    /**
     * An array of variable types for param/var we will check.
     *
     * @var string[]
     *
     * @deprecated 3.5.0 Use PHP_CodeSniffer\Util\Sniffs\Comments::$allowedTypes instead.
     */
    public static $allowedTypes = [
        'array',
        'boolean',
        'float',
        'integer',
        'mixed',
        'object',
        'string',
        'resource',
        'callable',
    ];


    /**
     * Return TRUE if the path is a PHAR file.
     *
     * @param string $path The path to use.
     *
     * @return mixed
     */
    public static function isPharFile($path)
    {
        if (strpos($path, 'phar://') === 0) {
            return true;
        }

        return false;

    }//end isPharFile()


    /**
     * CodeSniffer alternative for realpath.
     *
     * Allows for PHAR support.
     *
     * @param string $path The path to use.
     *
     * @return mixed
     */
    public static function realpath($path)
    {
        // Support the path replacement of ~ with the user's home directory.
        if (substr($path, 0, 2) === '~/') {
            $homeDir = getenv('HOME');
            if ($homeDir !== false) {
                $path = $homeDir.substr($path, 1);
            }
        }

        // Check for process substitution.
        if (strpos($path, '/dev/fd') === 0) {
            return str_replace('/dev/fd', 'php://fd', $path);
        }

        // No extra work needed if this is not a phar file.
        if (self::isPharFile($path) === false) {
            return realpath($path);
        }

        // Before trying to break down the file path,
        // check if it exists first because it will mostly not
        // change after running the below code.
        if (file_exists($path) === true) {
            return $path;
        }

        $phar  = \Phar::running(false);
        $extra = str_replace('phar://'.$phar, '', $path);
        $path  = realpath($phar);
        if ($path === false) {
            return false;
        }

        $path = 'phar://'.$path.$extra;
        if (file_exists($path) === true) {
            return $path;
        }

        return false;

    }//end realpath()


    /**
     * Removes a base path from the front of a file path.
     *
     * @param string $path     The path of the file.
     * @param string $basepath The base path to remove. This should not end
     *                         with a directory separator.
     *
     * @return string
     */
    public static function stripBasepath($path, $basepath)
    {
        if (empty($basepath) === true) {
            return $path;
        }

        $basepathLen = strlen($basepath);
        if (substr($path, 0, $basepathLen) === $basepath) {
            $path = substr($path, $basepathLen);
        }

        $path = ltrim($path, DIRECTORY_SEPARATOR);
        if ($path === '') {
            $path = '.';
        }

        return $path;

    }//end stripBasepath()


    /**
     * Detects the EOL character being used in a string.
     *
     * @param string $contents The contents to check.
     *
     * @return string
     */
    public static function detectLineEndings($contents)
    {
        if (preg_match("/\r\n?|\n/", $contents, $matches) !== 1) {
            // Assume there are no newlines.
            $eolChar = "\n";
        } else {
            $eolChar = $matches[0];
        }

        return $eolChar;

    }//end detectLineEndings()


    /**
     * Check if STDIN is a TTY.
     *
     * @return boolean
     */
    public static function isStdinATTY()
    {
        // The check is slow (especially calling `tty`) so we static
        // cache the result.
        static $isTTY = null;

        if ($isTTY !== null) {
            return $isTTY;
        }

        if (defined('STDIN') === false) {
            return false;
        }

        // If PHP has the POSIX extensions we will use them.
        if (function_exists('posix_isatty') === true) {
            $isTTY = (posix_isatty(STDIN) === true);
            return $isTTY;
        }

        // Next try is detecting whether we have `tty` installed and use that.
        if (defined('PHP_WINDOWS_VERSION_PLATFORM') === true) {
            $devnull = 'NUL';
            $which   = 'where';
        } else {
            $devnull = '/dev/null';
            $which   = 'which';
        }

        $tty = trim(shell_exec("$which tty 2> $devnull"));
        if (empty($tty) === false) {
            exec("tty -s 2> $devnull", $output, $returnValue);
            $isTTY = ($returnValue === 0);
            return $isTTY;
        }

        // Finally we will use fstat.  The solution borrowed from
        // https://stackoverflow.com/questions/11327367/detect-if-a-php-script-is-being-run-interactively-or-not
        // This doesn't work on Mingw/Cygwin/... using Mintty but they
        // have `tty` installed.
        $type = [
            'S_IFMT'  => 0170000,
            'S_IFIFO' => 0010000,
        ];

        $stat  = fstat(STDIN);
        $mode  = ($stat['mode'] & $type['S_IFMT']);
        $isTTY = ($mode !== $type['S_IFIFO']);

        return $isTTY;

    }//end isStdinATTY()


    /**
     * Prepares token content for output to screen.
     *
     * Replaces invisible characters so they are visible. On non-Windows
     * OSes it will also colour the invisible characters.
     *
     * @param string   $content The content to prepare.
     * @param string[] $exclude A list of characters to leave invisible.
     *                          Can contain \r, \n, \t and a space.
     *
     * @return string
     */
    public static function prepareForOutput($content, $exclude=[])
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (in_array("\r", $exclude, true) === false) {
                $content = str_replace("\r", '\r', $content);
            }

            if (in_array("\n", $exclude, true) === false) {
                $content = str_replace("\n", '\n', $content);
            }

            if (in_array("\t", $exclude, true) === false) {
                $content = str_replace("\t", '\t', $content);
            }
        } else {
            if (in_array("\r", $exclude, true) === false) {
                $content = str_replace("\r", "\033[30;1m\\r\033[0m", $content);
            }

            if (in_array("\n", $exclude, true) === false) {
                $content = str_replace("\n", "\033[30;1m\\n\033[0m", $content);
            }

            if (in_array("\t", $exclude, true) === false) {
                $content = str_replace("\t", "\033[30;1m\\t\033[0m", $content);
            }

            if (in_array(' ', $exclude, true) === false) {
                $content = str_replace(' ', "\033[30;1m·\033[0m", $content);
            }
        }//end if

        return $content;

    }//end prepareForOutput()


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
     *
     * @deprecated 3.5.0 Use PHP_CodeSniffer\Util\Sniffs\ConstructNames::isCamelCaps() instead.
     */
    public static function isCamelCaps(
        $string,
        $classFormat=false,
        $public=true,
        $strict=true
    ) {
        return Sniffs\ConstructNames::isCamelCaps($string, $classFormat, $public, $strict);

    }//end isCamelCaps()


    /**
     * Returns true if the specified string is in the underscore caps format.
     *
     * @param string $string The string to verify.
     *
     * @return boolean
     *
     * @deprecated 3.5.0 Use PHP_CodeSniffer\Util\Sniffs\ConstructNames::isUnderscoreName() instead.
     */
    public static function isUnderscoreName($string)
    {
        return Sniffs\ConstructNames::isUnderscoreName($string);

    }//end isUnderscoreName()


    /**
     * Returns a valid variable type for param/var tags.
     *
     * If type is not one of the standard types, it must be a custom type.
     * Returns the correct type name suggestion if type name is invalid.
     *
     * @param string $varType The variable type to process.
     *
     * @return string
     *
     * @deprecated 3.5.0 Use PHP_CodeSniffer\Util\Sniffs\Comments::suggestType() instead.
     */
    public static function suggestType($varType)
    {
        return Sniffs\Comments::suggestType($varType, 'long');

    }//end suggestType()


    /**
     * Given a sniff class name, returns the code for the sniff.
     *
     * @param string $sniffClass The fully qualified sniff class name.
     *
     * @return string
     */
    public static function getSniffCode($sniffClass)
    {
        $parts = explode('\\', $sniffClass);
        $sniff = array_pop($parts);

        if (substr($sniff, -5) === 'Sniff') {
            // Sniff class name.
            $sniff = substr($sniff, 0, -5);
        } else {
            // Unit test class name.
            $sniff = substr($sniff, 0, -8);
        }

        $category = array_pop($parts);
        $sniffDir = array_pop($parts);
        $standard = array_pop($parts);
        $code     = $standard.'.'.$category.'.'.$sniff;
        return $code;

    }//end getSniffCode()


    /**
     * Removes project-specific information from a sniff class name.
     *
     * @param string $sniffClass The fully qualified sniff class name.
     *
     * @return string
     */
    public static function cleanSniffClass($sniffClass)
    {
        $newName = strtolower($sniffClass);

        $sniffPos = strrpos($newName, '\sniffs\\');
        if ($sniffPos === false) {
            // Nothing we can do as it isn't in a known format.
            return $newName;
        }

        $end   = (strlen($newName) - $sniffPos + 1);
        $start = strrpos($newName, '\\', ($end * -1));

        if ($start === false) {
            // Nothing needs to be cleaned.
            return $newName;
        }

        $newName = substr($newName, ($start + 1));
        return $newName;

    }//end cleanSniffClass()


}//end class
