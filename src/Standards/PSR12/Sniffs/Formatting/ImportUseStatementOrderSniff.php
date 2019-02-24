<?php
/**
 * Enforce ordering of import `use` statements.
 *
 * "If present, each of the blocks below MUST be separated by a single blank line,
 *  and MUST NOT contain a blank line.
 *  Each block MUST be in the order listed below, although blocks that are
 *  not relevant may be omitted."
 *
 * - One or more class-based use import statements.
 * - One or more function-based use import statements.
 * - One or more constant-based use import statements.
 *
 * @link https://github.com/php-fig/fig-standards/blob/master/proposed/extended-coding-style-guide.md#3-declare-statements-namespace-and-import-statements
 *
 * {@internal Note: all the order related error messages have the same error code.
 *            As the fixer has to batch fix everything in one go as the new positions
 *            of statements can't yet be determined at the moment the errors are being thrown,
 *            disabling parts of the sniff via error codes would not work when using
 *            the fixer. Having the same errorcode prevents confusion about this.}}
 *
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2017-2019 Juliette Reinders Folmer. All rights reserved.
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\PSR12\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Common\Tokens;

class ImportUseStatementOrderSniff implements Sniff
{

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return array(T_USE);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param integer                     $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
	 * @return int|void Integer stack pointer to skip forward or void to continue
	 *                  normal file processing.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
		$tokens = $phpcsFile->getTokens();

// TODO: verify how mixed group use statements should be handled ? Where should those be placed ?
// Or should the sniff just not be auto-fixable if a mixed group use statement is found ?

		/*
		 * Find all namespace use statements and examine them.
		 */
		$usePtr = $stackPtr;
		$errors = 0;
		$fixes  = array();

		/*
		 * Complex array to store the found use statements.
		 *
		 * Primary key:   The stackPtr to the namespace the use statement belongs with.
		 * Secondary key: Statement type: class|function|const.
		 * Ternary key:   The stackPtr to the start of the use statement including
		 *                indentation and/or preceding comments.
		 *    Value:      The stackPtr to the end of the use statement including trailing comment(s).
		 */
		$use_statements     = array();
		$expected_start_pos = array();

		do {

// TODO: implement use of PHPCS native TokenIsUtils utility methods for this.

// TODO: implement skipping over OO structures, functions, closures, arrays as they won't contain import
// use statements anyway and should make the sniff faster as it walks the complete file.

			$use_type = $this->get_use_type( $usePtr );

			if ( 'class' !== $use_type ) {
				$usePtr = $this->phpcsFile->findNext( T_USE, ( $usePtr + 1 ) );
				continue;
			}

			/*
			 * Find which namespace this statement belongs with.
			 */
			$ns_token = $this->determine_namespace( $usePtr, true );
			if ( false === $ns_token ) {
				$ns_token = 'none'; // Prevent array key confusion.
			}

			/*
			 * Determine the class use type.
			 */
			$next = $this->phpcsFile->findNext( Tokens::$emptyTokens, ( $usePtr + 1 ), null, true );
			$type = 'class';
			if ( false !== $next && T_STRING === $this->tokens[ $next ]['code'] ) {
				if ( 'function' === $this->tokens[ $next ]['content'] ) {
					$type = 'function';
				} elseif ( 'const' === $this->tokens[ $next ]['content'] ) {
					$type = 'const';
				}
			}
			unset( $next );

			/*
			 * Find the start and end tokens for the use statement.
			 */
			$start = $this->findStartOfUseStatement($phpcsFile, $usePtr );
			$end   = $this->findEndOfUseStatement($phpcsFile, $usePtr );

			if ( false === $end ) {
				$usePtr = $this->phpcsFile->findNext( T_USE, ( $usePtr + 1 ) );
				continue;
			}

			if ( ! isset( $use_statements[ $ns_token ] ) ) {
				// Initialize the array.
				$use_statements[ $ns_token ] = array(
					'class'    => array(),
					'function' => array(),
					'const'    => array(),
				);

				$expected_start_pos[ $ns_token ] = $start;

				if ( 'none' !== $ns_token ) {
					/*
					 * Ok, this is the first use statement found for this namespace.
					 * Is it the first content after the namespace declaration ?
					 */
					$end_ns = $this->phpcsFile->findNext(
						array( T_SEMICOLON, T_OPEN_CURLY_BRACKET ),
						( $ns_token + 1 ),
						$usePtr
					);

					if ( false !== $end_ns ) {
						$next_non_empty = $this->phpcsFile->findNext(
							Tokens::$emptyTokens,
							( $end_ns + 1 ),
							null,
							true
						);

						if ( false !== $next_non_empty ) {
							if ( $next_non_empty !== $usePtr ) {
								$errors++;
								$fixes[] = $this->phpcsFile->addFixableError(
									'The first use statement should be the first content after the namespace statement.',
									$usePtr,
									'UseStatementOrder'
								);
							}

							$expected_start_pos[ $ns_token ] = $next_non_empty;
						}
					}
				}
			} elseif ( ! empty( $use_statements[ $ns_token ][ $type ] ) ) {
				/*
				 * Check whether this use statement directly follows the previous
				 * use statement of the same type.
				 */
				$end_prev = end( $use_statements[ $ns_token ][ $type ] );

				if ( ( $this->tokens[ $end_prev ]['line'] + 1 ) !== $this->tokens[ $start ]['line'] ) {

					$errors++;
					$fixes[] = $this->phpcsFile->addFixableError(
						'Each "use %s" statement should directly follow the previous "use %s" statement. Previous statement ended on line %s.',
						$usePtr,
						'UseStatementOrder',
						array( $type, $type, $this->tokens[ $end_prev ]['line'] )
					);
				}
			}

			$function_use_count = count( $use_statements[ $ns_token ]['function'] );
			$const_use_count    = count( $use_statements[ $ns_token ]['const'] );

			if ( 'class' === $type
				&& ( $function_use_count + $const_use_count ) > 0
			) {
				$errors++;
				$fixes[] = $this->phpcsFile->addFixableError(
					'Found a "use class" statement after a "use function" or "use const" statement.',
					$usePtr,
					'UseStatementOrder'
				);
			} elseif ( 'function' === $type && $const_use_count > 0 ) {
				$errors++;
				$fixes[] = $this->phpcsFile->addFixableError(
					'Found a "use function" statement after a "use const" statement.',
					$usePtr,
					'UseStatementOrder'
				);
			}

			$use_statements[ $ns_token ][ $type ][ $start ] = $end;

			$usePtr = $this->phpcsFile->findNext( T_USE, ( $usePtr + 1 ) );

		} while ( false !== $usePtr && $usePtr < $this->phpcsFile->numTokens );

		/*
		 * Batch fix all the use statements.
		 *
		 * As both the order and the start position may change, batching all
		 * fixes together looks to be the only way to avoid fixer conflicts
		 * and/or running out of fixer loops.
		 */
		$fixes = array_filter($fixes); // Remove 'false's.
		if ($errors > 0 && count($fixes) === $errors) {
			$this->batchFixStatementOrder($phpcsFile, $use_statements, $expected_start_pos);

			// Fix the blank lines in the next fixer round as they would be incorrect/conflicting now anyhow.
			return $phpcsFile->numTokens;
		}

		/*
		 * Check for a blank line before the first and after the last use statement in each block.
		 *
		 * If the use statement order was changed too, the fixers here will run in a subsequent loop.
		 */
		foreach ($use_statements as $ns_token => $types) {

			foreach ($types as $type => $statements) {
				if (empty($statements) === true) {
					continue;
				}

				$this->checkBlankLineBeforeGroup($phpcsFile, $statements, $type);
				$this->checkBlankLineAfterGroup($phpcsFile, $statements, $type);
			}
		}

		return $this->phpcsFile->numTokens;

	}//end process().


	/**
	 * Batch fix all the use statements.
	 *
	 * As both the order and the start position may change, batching
	 * all fixes together looks to be the only way to avoid fixer conflicts
	 * and/or running out of fixer loops.
	 *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile        The file being scanned.
	 * @param array                       $useStatements    Array with info on all
	 *                                                      namespace use statements
	 *                                                      found in the file.
	 * @param array                       $expectedStartPos Array with expected start
	 *                                                      position of use statements
	 *                                                      per namespace.
	 */
	protected function batchFixStatementOrder(File $phpcsFile, $useStatements, $expectedStartPos)
	{
		$tokens = $phpcsFile->getTokens();

		$phpcsFile->fixer->beginChangeset();

		foreach ($useStatements as $nsToken => $types) {

			$replace     = false;
			$replacement = '';

			foreach ($types as $type => $statements) {
				if (empty($statements) === true) {
					continue;
				}

				foreach ($statements as $start => $end) {
					/*
					 * {@internal Once upstream PR #1674 has been merged and the WPCS minimum
					 * PHPCS requirement has gone up to the version which contains that change,
					 * the third parameter `true` should be added to this function call and
					 * the unit test `fixed` files should be updated to reflect the improvement.
					 * Setting the tab-width in the unit test file can then also be removed.}}
					 * @link https://github.com/squizlabs/PHP_CodeSniffer/pull/1674
					 */
					$replacement .= $phpcsFile->getTokensAsString($start, (($end - $start) + 1), true);

					if (strpos($tokens[$end]['content'], $phpcsFile->eolChar) === false) {
						$replacement .= $phpcsFile->eolChar;
					}

					for ($i = $start; $i <= $end; $i++) {
						if ($i === $expectedStartPos[$nsToken]) {
							$replace = true;
							continue;
						}

						$phpcsFile->fixer->replaceToken($i, '');
					}
				}
			}

			// Add the complete block of use statements in the correct order below the namespace declaration.
			if ($replace === true) {
				$phpcsFile->fixer->replaceToken($expectedStartPos[$nsToken], $replacement);
			} else {
				$phpcsFile->fixer->addContentBefore($expectedStartPos[$nsToken], $replacement);
			}
		}

		$phpcsFile->fixer->endChangeset();

	}//end batchFixStatementOrder()

	/**
	 * Check for a blank line before the first statement in a block.
	 *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile  The file being scanned.
	 * @param array                       $statements Array with start/end pointers
	 *                                                of each use statement in a group.
	 * @param string                      $type       Use statement group type.
	 */
	protected function checkBlankLineBeforeGroup(File $phpcsFile, $statements, $type)
	{
		reset($statements);

		$tokens      = $phpcsFile->getTokens();
		$start       = key($statements);
		$prevContent = $phpcsFile->findPrevious(T_WHITESPACE, ($start - 1), null, true);
		if ($prevContent === false) {
			return;
		}

		$diff = ($tokens[$start]['line'] - $tokens[$prevContent]['line']);
		if ($diff === 2) {
			return;
		}

		if ($diff < 0) {
			$diff = 0;
		}

		$error = 'There must be exactly one blank line before a "use %s" statement group.';
		$data  = array($type);
		$fix   = $phpcsFile->addFixableError($error, $start, 'BlankLineBeforeGroup', $data);

		if ($fix === true) {
			switch ($diff) {
			case 0:
				$phpcsFile->fixer->addContentBefore($start, $phpcsFile->eolChar.$phpcsFile->eolChar);
				break;

			case 1:
				$phpcsFile->fixer->addContentBefore($start, $phpcsFile->eolChar);
				break;

			default:
				$phpcsFile->fixer->beginChangeset();
				for ($i = ($prevContent + 1); $i < $start; $i++) {
					if ($tokens[$i]['line'] === $tokens[$start]['line']) {
						break;
					}

					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->addContentBefore($start, $phpcsFile->eolChar.$phpcsFile->eolChar);
				$phpcsFile->fixer->endChangeset();
				break;
			}
		}

	}//end checkBlankLineBeforeGroup()

	/**
	 * Check for a blank line after the last statement in a block.
	 *
	 * We check for a minimum of one blank line and allow for two.
	 * If this is between two groups of use statement blocks, superfluous blank
	 * lines will be corrected by the "before" fixer of the next block.
	 * If this is at the end of all the use statements, allowing for more
	 * than one blank line will prevent conflicts with standards demanding
	 * two blank lines before classes/functions.
	 *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile  The file being scanned.
	 * @param array                       $statements Array with start/end pointers
	 *                                                of each use statement in a group.
	 * @param string                      $type       Use statement group type.
	 */
	protected function checkBlankLineAfterGroup(File $phpcsFile, $statements, $type)
	{
		$tokens      = $phpcsFile->getTokens();
		$end         = end($statements);
		$nextContent = $phpcsFile->findNext(T_WHITESPACE, ($end + 1), $phpcsFile->numTokens, true);
		if ($nextContent === false) {
			return;
		}

		$diff = ($tokens[$nextContent]['line'] - $tokens[($end + 1)]['line']);
		if ($diff === 1 || $diff === 2) {
			return;
		}

		if ($diff < 0) {
			$diff = 0;
		}

		$error = 'There must be a blank line after the last statement in a "use %s" statement group.';
		$data  = array($type);
		$fix   = $phpcsFile->addFixableError($error, $end, 'BlankLineAfterGroup', $data);

		if ($fix === true) {
			if ($diff === 0) {
				$phpcsFile->fixer->addNewlineBefore($end + 1);
			} else {
				$phpcsFile->fixer->beginChangeset();

				for ($i = ($end + 1); $i < $nextContent; $i++) {
					if ($tokens[$i]['line'] === $tokens[$next_content]['line']) {
						break;
					}

					$phpcsFile->fixer->replaceToken($i, '');
				}

				$phpcsFile->fixer->addNewline($end);
				$phpcsFile->fixer->endChangeset();
			}
		}

	}//end checkBlankLineAfterGroup()

	/**
	 * Find what should be regarded as the start of the statement.
	 *
	 * Comments directly before or above the statement should be included.
	 * Same goes for indentation before the use statement.
	 *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $usePtr    Stack pointer to the use keyword.
	 *
	 * @return int
	 */
	private function findStartOfUseStatement(File $phpcsFile, $usePtr)
	{
		$tokens      = $phpcsFile->getTokens();
		$lastNewline = null;

		for ($i = ($usePtr - 1); $i >= 0; $i--) {
			if (isset(Tokens::$emptyTokens[$tokens[$i]['code']]) === true) {
				if (strpos($tokens[$i]['content'], $phpcsFile->eolChar ) !== false) {
					if ($tokens[$i]['column'] === 1
						&& $tokens[$i]['code'] === T_WHITESPACE
						&& isset($tokens[($i - 1)]) === true
						&& $tokens[$i]['line'] !== $tokens[($i - 1)]['line']
					) {
						// Blank line found.
						break;
					}
					$lastNewline = $i;
				}
				continue;
			}
// TODO: check if this deals correctly with multi-statement without whitespace ...;use ...
			// Non empty token found.
			if (isset($lastNewline) === false
				&& $tokens[$i]['line'] === $tokens[($i + 1)]['line']
				&& $tokens[($i + 1)]['code'] === T_WHITESPACE
			) {
				// Deal with multiple statements on one line.
				++$i;
				break;
			}

			if (isset($lastNewline) === true) {
				$i = $lastNewline;
			}
			break;
		}

		return ++$i;

	}//end findStartOfUseStatement()

// TODO: account for situations where the use statement ends with a T_CLOSE_TAG!!!!

	/**
	 * Find the last token for the complete use statement, including trailing comments
	 * and the new line token.
	 *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param int                         $usePtr    Stack pointer to the use keyword.
	 *
	 * @return int|bool Integer stack pointer or false when it couldn't be determined.
	 */
	private function findEndOfUseStatement(File $phpcsFile,  $usePtr)
	{
		$tokens    = $phpcsFile->getTokens();
		$semicolon = $phpcsFile->findNext(T_SEMICOLON, ($usePtr + 1), null, false, null, true);
		if ($semicolon === false) {
			// Live coding.
			return false;
		}

		for ($end = ($semicolon + 1); $end < $phpcsFile->numTokens; $end++) {
			if (isset( Tokens::$emptyTokens[$tokens[$end]['code']]) === true
				&& strpos($tokens[$end]['content'], $phpcsFile->eolChar) === false
				&& $tokens[$end]['line'] === $tokens[($end - 1)]['line']
			) {
				continue;
			}

			// Deal with multiple statements on one line.
			if (isset(Tokens::$emptyTokens[$tokens[$end ]['code']]) === false
				&& $tokens[$end]['line'] === $tokens[($end - 1)]['line']
			) {
				$end = $semicolon;
			}

			break;
		}

		return $end;

	}//end findEndOfUseStatement()

} // End class.
