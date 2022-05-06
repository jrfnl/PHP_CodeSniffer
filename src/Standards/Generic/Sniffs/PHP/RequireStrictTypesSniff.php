<?php
/**
 * Checks that the strict_types has been declared.
 *
 * @author    Sertan Danis <sdanis@squiz.net>
 * @copyright 2006-2019 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class RequireStrictTypesSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_OPEN_TAG];

    }//end register()


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in
     *                                               the stack passed in $tokens.
     *
     * @return int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens  = $phpcsFile->getTokens();
        $declare = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        
        /*
         - Run through potentially multiple declare statements at start of file, don't scan the whole bloody file
         - Within a declare, check each T_STRING as one declare can have comma-separated multiple directives.
         - Once strict_types is found, verify that it is set to 1
         -> if set to 0, fix to 1
         -> if not set, add it
         */


        if ($declare !== false && $tokens[$declare]['code'] === T_DECLARE) {
            $nextString = $phpcsFile->findNext(T_STRING, $declare);

            if ($nextString !== false) {
                if (strtolower($tokens[$nextString]['content']) === 'strict_types') {
                    // There is a strict types declaration.
                    $found = true;
                }
            }
		}

        $found   = false;

        if ($declare !== false) {
        }

        if ($found === false) {
            $error = 'Missing required strict_types declaration';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'MissingDeclaration');
            
            if ($fix === true) {
			    $phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContentBefore($stackPtr, "declare(strict_types=1);\n");
			    $phpcsFile->fixer->endChangeset();
			}
        }

        // Skip the rest of the file so we don't pick up additional
        // open tags, typically embedded in HTML.
        return $phpcsFile->numTokens;

    }//end process()


}//end class
