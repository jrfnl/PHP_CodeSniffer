<?php
/**
 * Verifies that class members are spaced correctly.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Tokens;

class MemberVarSpacingSniff extends AbstractVariableSniff
{

    /**
     * The number of blank lines between member vars.
     *
     * @var integer
     */
    public $spacing = 1;

    /**
     * The number of blank lines before the first member var.
     *
     * @var integer
     */
    public $spacingBeforeFirst = 1;


    /**
     * Processes the function tokens within the class.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position where the token was found.
     *
     * @return void|int Optionally returns a stack pointer. The sniff will not be
     *                  called again on the current file until the returned stack
     *                  pointer is reached.
     */
    protected function processMemberVar(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $stopPoints = [
            T_SEMICOLON,
            T_OPEN_CURLY_BRACKET,
            T_CLOSE_CURLY_BRACKET,
        ];

        $endOfPreviousStatement = $phpcsFile->findPrevious($stopPoints, ($stackPtr - 1), null, false, null, true);

        $validPrefixes   = Tokens::$methodPrefixes;
        $validPrefixes[] = T_VAR;
        $validPrefixes[] = T_READONLY;

        $startOfStatement = $phpcsFile->findNext($validPrefixes, ($endOfPreviousStatement + 1), $stackPtr, false, null, true);
        if ($startOfStatement === false) {
            // Parse error/live coding - property without modifier. Bow out.
            return;
        }

        $endOfStatement = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1), null, false, null, true);

        $start       = $startOfStatement;
        $seenComment = false;
        for ($prev = ($startOfStatement - 1); $prev >= 0; $prev--) {
            if ($tokens[$prev]['code'] === T_WHITESPACE) {
                continue;
            }

            if (isset($tokens[$prev]['attribute_opener']) === true) {
                $prev  = $tokens[$prev]['attribute_opener'];
                $start = $prev;
                continue;
            }

            if ($seenComment === false && $tokens[$prev]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
                $prev        = $tokens[$prev]['comment_opener'];
                $start       = $prev;
                $seenComment = true;
                continue;
            }

            // Check for non-docblock comments and annotations.
            // Assume the comment belongs to the member var if it is on a line by itself
            // and we've not yet seen a docblock.
            if ($seenComment === false && isset(Tokens::$commentTokens[$tokens[$prev]['code']]) === true) {
                $prevContent = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prev - 1), null, true);
                if ($tokens[$prevContent]['line'] !== $tokens[$prev]['line']) {
                    // The comment is on a line by itself.
                    // Now find the first line of a potentially multi-line comment.
                    $startOfComment = $prev;
                    for ($find = ($prev - 1); $find > 0; $find--) {
                        if ($tokens[$find]['code'] === T_WHITESPACE) {
                            continue;
                        }

                        if (isset(Tokens::$commentTokens[$tokens[$find]['code']]) === true
                            && $tokens[$find]['line'] === ($tokens[$startOfComment]['line'] - 1)
                        ) {
                            $startOfComment = $find;
                            continue;
                        }

                        break;
                    }

                    $start       = $startOfComment;
                    $seenComment = true;
                }//end if
            }//end if

            break;
        }//end for

        // Check there are no blank lines in the preamble for the property,
        // but ignore blank lines _within_ docblocks and attributes as that's not the concern of this sniff.
        for ($i = ($start + 1); $i < $startOfStatement; $i++) {
            // Skip over the contents of docblocks and attributes.
            if (isset($tokens[$i]['attribute_closer']) === true) {
                $i = $tokens[$i]['attribute_closer'];
                continue;
            }

            if (isset($tokens[$i]['comment_closer']) === true) {
                $i = $tokens[$i]['comment_closer'];
                continue;
            }

            if ($tokens[$i]['column'] !== 1
                || $tokens[$i]['code'] !== T_WHITESPACE
                || $tokens[$i]['line'] === $tokens[($i + 1)]['line']
                // Do not report blank lines after a PHPCS annotation as removing the blank lines could change the meaning.
                || isset(Tokens::$phpcsCommentTokens[$tokens[($i - 1)]['code']]) === true
            ) {
                continue;
            }

            // We found a blank line which should be reported.
            $nextNonWhitespace = $phpcsFile->findNext(T_WHITESPACE, ($i + 1), null, true);
            $foundLines        = ($tokens[$nextNonWhitespace]['line'] - $tokens[$i]['line']);

            $error = 'Expected no blank lines between the member var comment/attributes and the declaration; %s found';
            $data  = [$foundLines];
            $fix   = $phpcsFile->addFixableError($error, $i, 'BlankLineInPreamble', $data);

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                for ($j = $i; $j < $nextNonWhitespace; $j++) {
                    if ($tokens[$j]['line'] === $tokens[$nextNonWhitespace]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($j, '');
                }

                $phpcsFile->fixer->endChangeset();
            }

            $i = $nextNonWhitespace;
        }//end for

        // There needs to be n blank lines before the var, not counting comments/attributes.
        $first = $phpcsFile->findFirstOnLine(Tokens::$emptyTokens, $start, true);
        if ($first === false) {
            $first = $start;
        }

        // Prevent conflict with function spacing sniffs.
        $prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($first - 1), null, true);
        if ($tokens[$prev]['code'] === T_CLOSE_CURLY_BRACKET
            && isset($tokens[$prev]['scope_condition']) === true
            && $tokens[$tokens[$prev]['scope_condition']]['code'] === T_FUNCTION
        ) {
            return;
        }

        // Determine if this is the first member var.
        if ($tokens[$prev]['code'] === T_OPEN_CURLY_BRACKET
            && isset(Tokens::$ooScopeTokens[$tokens[$tokens[$prev]['scope_condition']]['code']]) === true
        ) {
            $errorMsg  = 'Expected %s blank line(s) before first member var; %s found';
            $errorCode = 'FirstIncorrect';
            $spacing   = (int) $this->spacingBeforeFirst;
        } else {
            $errorMsg  = 'Expected %s blank line(s) before member var; %s found';
            $errorCode = 'Incorrect';
            $spacing   = (int) $this->spacing;
        }

        $foundLines = ($tokens[$first]['line'] - $tokens[$prev]['line'] - 1);

        if ($errorCode === 'FirstIncorrect') {
            $phpcsFile->recordMetric($stackPtr, 'Member var spacing before first', $foundLines);
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Member var spacing before', $foundLines);
        }

        if ($foundLines === $spacing) {
            if ($endOfStatement !== false) {
                return $endOfStatement;
            }

            return;
        }

        $data = [
            $spacing,
            $foundLines,
        ];

        $fix = $phpcsFile->addFixableError($errorMsg, $startOfStatement, $errorCode, $data);
        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();
            for ($i = ($prev + 1); $i < $first; $i++) {
                if ($tokens[$i]['line'] === $tokens[$prev]['line']) {
                    continue;
                }

                if ($tokens[$i]['line'] === $tokens[$first]['line']) {
                    for ($x = 1; $x <= $spacing; $x++) {
                        $phpcsFile->fixer->addNewlineBefore($i);
                    }

                    break;
                }

                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();
        }//end if

        if ($endOfStatement !== false) {
            return $endOfStatement;
        }

    }//end processMemberVar()


    /**
     * Processes normal variables.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position where the token was found.
     *
     * @return void
     */
    protected function processVariable(File $phpcsFile, $stackPtr)
    {
        /*
            We don't care about normal variables.
        */

    }//end processVariable()


    /**
     * Processes variables in double quoted strings.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where this token was found.
     * @param int                         $stackPtr  The position where the token was found.
     *
     * @return void
     */
    protected function processVariableInString(File $phpcsFile, $stackPtr)
    {
        /*
            We don't care about normal variables.
        */

    }//end processVariableInString()


}//end class
