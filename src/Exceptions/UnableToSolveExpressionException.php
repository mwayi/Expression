<?php

namespace Smrtr\Expression\Exceptions;

use Exception; 

class UnableToSolveExpressionException extends Exception
{
	/**
	 * Badly formatted condition constructor.
	 *
	 * @param  string|null $message
	 * @param  integer $code
	 * @param  Exception|null $previous
	 * @return void
	 */
	public function __construct($message = null, $code = 0, Exception $previous = null) 
	{
		parent::__construct($message, $code, $previous);
	}

	/**
     * Output to string.
     *
     * @return string $message the exception message.
     */
	public function __toString() 
	{
		return __CLASS__ . ": [[{$this->code}]]: Unable to evaluate resulting expression. You must ensure that all conditions return truthy or falsey values only\n";
	}
} 