<?php
/**
 * Checks the declaration of the class and its inheritance is correct.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR2\Sniffs\Classes;

use PHP_CodeSniffer\Standards\PEAR\Sniffs\Classes\ClassDeclarationSniff as PEARClassDeclarationSniff;
use PHP_CodeSniffer\Util\Tokens;
use PHP_CodeSniffer\Files\File;

class ClassDeclarationSniff extends PEARClassDeclarationSniff
{

    /**
     * The number of spaces code should be indented.
     *
     * @var integer
     */
    public $indent = 4;


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // We want all the errors from the PEAR standard, plus some of our own.
        parent::process($phpcsFile, $stackPtr);

        // Just in case.
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        $this->processOpen($phpcsFile, $stackPtr);
        $this->processClose($phpcsFile, $stackPtr);

    }//end process()


    /**
     * Processes the opening section of a class declaration.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function processOpen(File $phpcsFile, $stackPtr)
    {
        $tokens       = $phpcsFile->getTokens();
        $stackPtrType = strtolower($tokens[$stackPtr]['content']);

        // Check alignment of the keyword and braces.
        $prevNonEmpty = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($prevNonEmpty !== false
            && ($tokens[$prevNonEmpty]['code'] === T_ABSTRACT
            || $tokens[$prevNonEmpty]['code'] === T_FINAL)
        ) {
            $prevContent       = strtolower($tokens[$prevNonEmpty]['content']);
            $prevNonWhitespace = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
            $fixable           = false;
            if ($prevNonWhitespace === $prevNonEmpty) {
                // Only make this fixable when no comments or annotations have been found.
                $fixable = true;
            }

            if ($tokens[$stackPtr]['line'] === $tokens[$prevNonEmpty]['line']) {
                $errorCode = 'SpaceBeforeKeyword';
                if ($fixable === true) {
                    if ($tokens[($stackPtr - 1)]['length'] !== 1) {
                        $error = 'Expected 1 space between %s and %s keywords; %s found';
                        $data  = [
                            $prevContent,
                            $stackPtrType,
                            $tokens[($stackPtr - 1)]['length'],
                        ];

                        $fix = $phpcsFile->addFixableError($error, $stackPtr, $errorCode, $data);
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken(($stackPtr - 1), ' ');
                        }
                    }
                } else {
                    // If it's non-fixable, we know there is an unexpected comment.
                    $error = 'Expected 1 space between %s and %s keywords; comment found';
                    $data  = [
                        $prevContent,
                        $stackPtrType,
                    ];

                    $phpcsFile->addError($error, $stackPtr, $errorCode, $data);
                }//end if
            } else {
                $error     = 'Expected 1 space between %s and %s keywords; newline found';
                $errorCode = 'NewlineBeforeKeyword';
                $data      = [
                    $prevContent,
                    $stackPtrType,
                ];

                if ($fixable === false) {
                    $phpcsFile->addError($error, $stackPtr, $errorCode, $data);
                } else {
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, $errorCode, $data);
                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        for ($i = ($prevNonWhitespace + 1); $i < ($stackPtr - 1); $i++) {
                            $phpcsFile->fixer->replaceToken($i, '');
                        }

                        $phpcsFile->fixer->replaceToken(($stackPtr - 1), ' ');
                        $phpcsFile->fixer->endChangeset();
                    }
                }
            }//end if
            unset($prevContent, $prevNonWhitespace, $fixable, $errorCode, $error, $data, $fix);
        }//end if
        unset($prevNonEmpty);

        // We'll need the indent of the class/interface declaration for later.
        $classIndent = 0;
        for ($i = ($stackPtr - 1); $i > 0; $i--) {
            if ($tokens[$i]['line'] === $tokens[$stackPtr]['line']) {
                continue;
            }

            // We changed lines.
            if ($tokens[($i + 1)]['code'] === T_WHITESPACE) {
                $classIndent = $tokens[($i + 1)]['length'];
            }

            break;
        }

        $className = $phpcsFile->findNext(T_STRING, $stackPtr);

        // Spacing of the keyword.
        $gapSize = $tokens[($stackPtr + 1)]['length'];
        if ($gapSize !== 1) {
            $found = $gapSize;
            $error = 'Expected 1 space between %s keyword and %s name; %s found';
            $data  = [
                $stackPtrType,
                $stackPtrType,
                $found,
            ];

            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpaceAfterKeyword', $data);
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($stackPtr + 1), ' ');
            }
        }

        // Check after the class/interface name.
        if ($tokens[($className + 2)]['line'] === $tokens[$className]['line']) {
            $gapSize = $tokens[($className + 1)]['length'];
            if ($gapSize !== 1) {
                $found = $gapSize;
                $error = 'Expected 1 space after %s name; %s found';
                $data  = [
                    $stackPtrType,
                    $found,
                ];

                $fix = $phpcsFile->addFixableError($error, $className, 'SpaceAfterName', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($className + 1), ' ');
                }
            }
        }

        $openingBrace = $tokens[$stackPtr]['scope_opener'];

        // Check positions of the extends and implements keywords.
        foreach (['extends', 'implements'] as $keywordType) {
            $keyword = $phpcsFile->findNext(constant('T_'.strtoupper($keywordType)), ($stackPtr + 1), $openingBrace);
            if ($keyword !== false) {
                if ($tokens[$keyword]['line'] !== $tokens[$stackPtr]['line']) {
                    $error = 'The %s keyword must be on the same line as the %s name';
                    $data  = [
                        $keywordType,
                        $stackPtrType,
                    ];
                    $fix   = $phpcsFile->addFixableError($error, $keyword, ucfirst($keywordType).'Line', $data);
                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        for ($i = ($stackPtr + 1); $i < $keyword; $i++) {
                            if ($tokens[$i]['line'] !== $tokens[($i + 1)]['line']) {
                                $phpcsFile->fixer->substrToken($i, 0, (strlen($phpcsFile->eolChar) * -1));
                            }
                        }

                        $phpcsFile->fixer->addContentBefore($keyword, ' ');
                        $phpcsFile->fixer->endChangeset();
                    }
                } else {
                    // Check the whitespace before. Whitespace after is checked
                    // later by looking at the whitespace before the first class name
                    // in the list.
                    $gapSize = $tokens[($keyword - 1)]['length'];
                    if ($gapSize !== 1) {
                        $error = 'Expected 1 space before %s keyword; %s found';
                        $data  = [
                            $keywordType,
                            $gapSize,
                        ];
                        $fix   = $phpcsFile->addFixableError($error, $keyword, 'SpaceBefore'.ucfirst($keywordType), $data);
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken(($keyword - 1), ' ');
                        }
                    }
                }//end if
            }//end if
        }//end foreach

        // Check each of the extends/implements class names. If the extends/implements
        // keyword is the last content on the line, it means we need to check for
        // the multi-line format, so we do not include the class names
        // from the extends/implements list in the following check.
        // Note that classes can only extend one other class, so they can't use a
        // multi-line extends format, whereas an interface can extend multiple
        // other interfaces, and so uses a multi-line extends format.
        if ($tokens[$stackPtr]['code'] === T_INTERFACE) {
            $keywordTokenType = T_EXTENDS;
        } else {
            $keywordTokenType = T_IMPLEMENTS;
        }

        $implements          = $phpcsFile->findNext($keywordTokenType, ($stackPtr + 1), $openingBrace);
        $multiLineImplements = false;
        if ($implements !== false) {
            $prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($openingBrace - 1), $implements, true);
            if ($tokens[$prev]['line'] !== $tokens[$implements]['line']) {
                $multiLineImplements = true;
            }
        }

        $find = [
            T_STRING,
            $keywordTokenType,
        ];

        $classNames = [];
        $nextClass  = $phpcsFile->findNext($find, ($className + 2), ($openingBrace - 1));
        while ($nextClass !== false) {
            $classNames[] = $nextClass;
            $nextClass    = $phpcsFile->findNext($find, ($nextClass + 1), ($openingBrace - 1));
        }
ini_set( 'xdebug.overload_var_dump', 1 );
$temp_fordisplay = array();
foreach($classNames as $_token ) {
    $temp_fordisplay[] = $tokens[$_token]['line'].':'.$tokens[$_token]['content'];
}
var_dump($temp_fordisplay);
        $classCount         = count($classNames);
        $checkingImplements = false;
        $implementsToken    = null;
        foreach ($classNames as $i => $className) {
            if ($tokens[$className]['code'] === $keywordTokenType) {
                $checkingImplements = true;
                $implementsToken    = $className;
                continue;
            }

            if ($checkingImplements === true
                && $multiLineImplements === true
                && ($tokens[($className - 1)]['code'] !== T_NS_SEPARATOR
                || $tokens[($className - 2)]['code'] !== T_STRING)
            ) {
                $prev = $phpcsFile->findPrevious(
                    [
                        T_NS_SEPARATOR,
                        T_WHITESPACE,
                    ],
                    ($className - 1),
                    $implements,
                    true
                );

                if ($prev === $implementsToken && $tokens[$className]['line'] !== ($tokens[$prev]['line'] + 1)) {
                    if ($keywordTokenType === T_EXTENDS) {
                        $error = 'The first item in a multi-line extends list must be on the line following the extends keyword';
                        $fix   = $phpcsFile->addFixableError($error, $className, 'FirstExtendsInterfaceSameLine');
                    } else {
                        $error = 'The first item in a multi-line implements list must be on the line following the implements keyword';
                        $fix   = $phpcsFile->addFixableError($error, $className, 'FirstInterfaceSameLine');
                    }

                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        for ($i = ($prev + 1); $i < $className; $i++) {
                            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                                break;
                            }

                            $phpcsFile->fixer->replaceToken($i, '');
                        }

                        $phpcsFile->fixer->addNewline($prev);
                        $phpcsFile->fixer->endChangeset();
                    }
                } else if ($tokens[$prev]['line'] !== ($tokens[$className]['line'] - 1)) {
                    if ($keywordTokenType === T_EXTENDS) {
                        $error = 'Only one interface may be specified per line in a multi-line extends declaration';
                        $fix   = $phpcsFile->addFixableError($error, $className, 'ExtendsInterfaceSameLine');
                    } else {
                        $error = 'Only one interface may be specified per line in a multi-line implements declaration';
                        $fix   = $phpcsFile->addFixableError($error, $className, 'InterfaceSameLine');
                    }

                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        for ($i = ($prev + 1); $i < $className; $i++) {
                            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                                break;
                            }

                            $phpcsFile->fixer->replaceToken($i, '');
                        }

                        $phpcsFile->fixer->addNewline($prev);
                        $phpcsFile->fixer->endChangeset();
                    }
                } else {
                    $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($className - 1), $implements);
                    if ($tokens[$prev]['line'] !== $tokens[$className]['line']) {
                        $found = 0;
                    } else {
                        $found = $tokens[$prev]['length'];
                    }

                    $expected = ($classIndent + $this->indent);
                    if ($found !== $expected) {
                        $error = 'Expected %s spaces before interface name; %s found';
                        $data  = [
                            $expected,
                            $found,
                        ];
                        $fix   = $phpcsFile->addFixableError($error, $className, 'InterfaceWrongIndent', $data);
                        if ($fix === true) {
                            $padding = str_repeat(' ', $expected);
                            if ($found === 0) {
                                $phpcsFile->fixer->addContent($prev, $padding);
                            } else {
                                $phpcsFile->fixer->replaceToken($prev, $padding);
                            }
                        }
                    }
                }//end if
            } else if ($tokens[($className - 1)]['code'] !== T_NS_SEPARATOR
                || $tokens[($className - 2)]['code'] !== T_STRING
            ) {
                // Not part of a longer fully qualified class name.
                if ($tokens[($className - 1)]['code'] === T_COMMA
                    || ($tokens[($className - 1)]['code'] === T_NS_SEPARATOR
                    && $tokens[($className - 2)]['code'] === T_COMMA)
                ) {
                    $error = 'Expected 1 space before "%s"; 0 found';
                    $data  = [$tokens[$className]['content']];
                    $fix   = $phpcsFile->addFixableError($error, ($nextComma + 1), 'NoSpaceBeforeName', $data);
                    if ($fix === true) {
                        $phpcsFile->fixer->addContentBefore(($nextComma + 1), ' ');
                    }
                } else {
                    if ($tokens[($className - 1)]['code'] === T_NS_SEPARATOR) {
                        $prev = ($className - 2);
                    } else {
                        $prev = ($className - 1);
                    }

                    $spaceBefore = $tokens[$prev]['length'];
                    if ($spaceBefore !== 1) {
                        $error = 'Expected 1 space before "%s"; %s found';
                        $data  = [
                            $tokens[$className]['content'],
                            $spaceBefore,
                        ];

                        $fix = $phpcsFile->addFixableError($error, $className, 'SpaceBeforeName', $data);
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken($prev, ' ');
                        }
                    }
                }//end if
            }//end if

            if ($checkingImplements === true
                && $tokens[($className + 1)]['code'] !== T_NS_SEPARATOR
                && $tokens[($className + 1)]['code'] !== T_COMMA
            ) {
                if ($i !== ($classCount - 1)) {
                    // This is not the last class name, and the comma
                    // is not where we expect it to be.
                    if ($tokens[($className + 2)]['code'] !== $keywordTokenType) {
                        $error = 'Expected 0 spaces between "%s" and comma; %s found';
                        $data  = [
                            $tokens[$className]['content'],
                            $tokens[($className + 1)]['length'],
                        ];

                        $fix = $phpcsFile->addFixableError($error, $className, 'SpaceBeforeComma', $data);
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken(($className + 1), '');
                        }
                    }
                }

                $nextComma = $phpcsFile->findNext(T_COMMA, $className);
            } else {
                $nextComma = ($className + 1);
            }//end if
        }//end foreach

    }//end processOpen()


    /**
     * Processes the closing section of a class declaration.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function processClose(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check that the closing brace comes right after the code body.
        $closeBrace  = $tokens[$stackPtr]['scope_closer'];
        $prevContent = $phpcsFile->findPrevious(T_WHITESPACE, ($closeBrace - 1), null, true);
        if ($prevContent !== $tokens[$stackPtr]['scope_opener']
            && $tokens[$prevContent]['line'] !== ($tokens[$closeBrace]['line'] - 1)
        ) {
            $error = 'The closing brace for the %s must go on the next line after the body';
            $data  = [$tokens[$stackPtr]['content']];
            $fix   = $phpcsFile->addFixableError($error, $closeBrace, 'CloseBraceAfterBody', $data);

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($prevContent + 1); $i < $closeBrace; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                if (strpos($tokens[$prevContent]['content'], $phpcsFile->eolChar) === false) {
                    $phpcsFile->fixer->replaceToken($closeBrace, $phpcsFile->eolChar.$tokens[$closeBrace]['content']);
                }

                $phpcsFile->fixer->endChangeset();
            }
        }//end if

        // Check the closing brace is on it's own line, but allow
        // for comments like "//end class".
        $ignoreTokens   = Tokens::$phpcsCommentTokens;
        $ignoreTokens[] = T_WHITESPACE;
        $ignoreTokens[] = T_COMMENT;
        $nextContent    = $phpcsFile->findNext($ignoreTokens, ($closeBrace + 1), null, true);
        if ($tokens[$nextContent]['content'] !== $phpcsFile->eolChar
            && $tokens[$nextContent]['line'] === $tokens[$closeBrace]['line']
        ) {
            $type  = strtolower($tokens[$stackPtr]['content']);
            $error = 'Closing %s brace must be on a line by itself';
            $data  = [$type];
            $phpcsFile->addError($error, $closeBrace, 'CloseBraceSameLine', $data);
        }

    }//end processClose()


}//end class
