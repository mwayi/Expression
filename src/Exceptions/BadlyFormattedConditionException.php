<?php

namespace Smrtr\Expression\Exceptions;

use Exception; 

class BadlyFormattedConditionException extends Exception
{
	/**
	 * Badly formatted condition constructor.
	 *
	 * @param  string $message
	 * @param  integer $code
	 * @param  Exception|null $previous
	 * @return void
	 */
	public function __construct($message, $code = 0, Exception $previous = null) 
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
		return __CLASS__ . ": [[{$this->code}]]: Unable to resolve expression [[$this->message]] as it is incomplete. A statement must have a key, comparison operator and a value\n";
	}
} 