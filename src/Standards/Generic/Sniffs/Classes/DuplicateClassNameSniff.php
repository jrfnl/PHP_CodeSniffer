<?php
/**
 * Reports errors if the same class or interface name is used in multiple files.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Generic\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Sniffs\Namespaces;

class DuplicateClassNameSniff implements Sniff
{

    /**
     * List of classes that have been found during checking.
     *
     * @var array
     */
    protected $foundClasses = [];

    /**
     * The name of the last file seen.
     *
     * @var string
     */
    private $currentFile = '';

    /**
     * The name of the current namespace.
     *
     * @var string
     */
    private $currentNamespace = '';


    /**
     * Registers the tokens that this sniff wants to listen for.
     *
     * @return int[]
     */
    public function register()
    {
        return [T_OPEN_TAG];

    }//end register()


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
        $tokens   = $phpcsFile->getTokens();
        $fileName = $phpcsFile->getFilename();

        if ($fileName !== $this->currentFile) {
            $this->currentNamespace = '';
            $this->currentFile      = $fileName;
        }

        $findTokens = [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
            T_NAMESPACE,
            T_CLOSE_TAG,
        ];

        $stackPtr = $phpcsFile->findNext($findTokens, ($stackPtr + 1));
        while ($stackPtr !== false) {
            if ($tokens[$stackPtr]['code'] === T_CLOSE_TAG) {
                // We can stop here. The sniff will continue from the next open
                // tag when PHPCS reaches that token, if there is one.
                return;
            }

            // Keep track of what namespace we are in.
            if ($tokens[$stackPtr]['code'] === T_NAMESPACE) {
                $newNamespace = Namespaces::getDeclaredName($phpcsFile, $stackPtr);
                if ($newNamespace !== false) {
                    $this->currentNamespace = $newNamespace;
                    $stackPtr = $phpcsFile->findNext(Namespaces::$statementClosers, ($stackPtr + 1));

                    if ($tokens[$stackPtr]['code'] === T_CLOSE_TAG) {
                        // Namespace declaration ended on a close tag.
                        return;
                    }
                }
            } else {
                $nameToken = $phpcsFile->findNext(T_STRING, $stackPtr);
                $name      = $tokens[$nameToken]['content'];
                if ($this->currentNamespace !== '') {
                    $name = $this->currentNamespace.'\\'.$name;
                }

                $compareName = strtolower($name);
                if (isset($this->foundClasses[$compareName]) === true) {
                    $type  = strtolower($tokens[$stackPtr]['content']);
                    $file  = $this->foundClasses[$compareName]['file'];
                    $line  = $this->foundClasses[$compareName]['line'];
                    $error = 'Duplicate %s name "%s" found; first defined in %s on line %s';
                    $data  = [
                        $type,
                        $name,
                        $file,
                        $line,
                    ];
                    $phpcsFile->addWarning($error, $stackPtr, 'Found', $data);
                } else {
                    $this->foundClasses[$compareName] = [
                        'file' => $phpcsFile->getFilename(),
                        'line' => $tokens[$stackPtr]['line'],
                    ];
                }
            }//end if

            $stackPtr = $phpcsFile->findNext($findTokens, ($stackPtr + 1));
        }//end while

    }//end process()


}//end class
