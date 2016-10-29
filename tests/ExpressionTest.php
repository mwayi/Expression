<?php

namespace Smrtr\Expression;

use Smrtr\Expression\Condition;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{	
	public function testSingleCondition()
	{
		$expression = new Expression('a = b');
		$conditions = $expression->toArray();

		$this->assertEquals(1, count($conditions));

		$condition = array_shift($conditions);
		$this->assertInstanceOf(Condition::class, $condition);
		
		$this->assertEquals('a', $condition->key);
		$this->assertEquals('=', $condition->operator);
		$this->assertEquals('b', $condition->value);
	}
}