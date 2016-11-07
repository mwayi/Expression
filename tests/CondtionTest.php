<?php

namespace Smrtr\Expression;

use Smrtr\Expression\Condition;

class ConditionTest extends \PHPUnit_Framework_TestCase
{	
	/**
	 * Test validity of single condition.
	 */
	public function testHasSingleConditionOnCondition()
	{
		$condition = new Condition('a = b');

		$this->assertEquals('a', $condition->key);
		$this->assertEquals('=', $condition->operator);
		$this->assertEquals('b', $condition->value);
	}
}