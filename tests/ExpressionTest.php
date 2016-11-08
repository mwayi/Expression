<?php

namespace Smrtr\Expression;

use Smrtr\Expression\Condition;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{	
	/**
	 * Test validity of single condition.
	 */
	public function testHasSingleCondition()
	{
		$expression = new Expression('a = b');
		$conditions = $expression->execute()->toArray();

		$this->assertEquals(1, count($conditions));

		$condition = array_shift($conditions);
		$this->assertInstanceOf(Condition::class, $condition);

		$this->assertEquals('a', $condition->key);
		$this->assertEquals('=', $condition->operator);
		$this->assertEquals('b', $condition->value);
	}

	/**
	 * Test condition has correct elements.
	 */
	public function testConditionHasCorrectElements()
	{
		$condition = new Condition('a = b');
		
		$this->assertEquals('a', $condition->key);
		$this->assertEquals('=', $condition->operator);
		$this->assertEquals('b', $condition->value);
	}

	/**
	 * Test expression can add logical operators.
	 */
	public function testExpressionHasLogicOperator()
	{
		$expression = new Expression('a = 3 xor b = 2');
		$expression->addLogicalOperator('exor');

		$this->assertContains('exor', $expression->getLogicalOperators());
	}
}