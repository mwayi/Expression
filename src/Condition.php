<?php

namespace Smrtr\Expression;

use Smrtr\Expression\Exceptions\BadlyFormattedConditionException;

class Condition
{
	/**
	 * @var array
	 */
	protected $attributes = [
		'key' => null,
		'value' => null,
		'original_value' => null,
		'operator' => null,
	];

	/**
	 * @param string $expression
	 */
	public function __construct($condition)
	{
		$this->resolve($condition);
	}

	/**
	 * Get attributes.
	 *
	 * @param string $attribute
	 * @return string
	 */
	public function __get($attribute)
	{
		if (array_key_exists($attribute, $this->attributes)) {
			return $this->attributes[$attribute];
		}

		throw new \Exception('Attribute [['.$attribute.']] does not exist.');
	}

	/**
	 * Get attributes.
	 *
	 * @param string $attribute
	 * @param mixed  $value
	 * @return string
	 */
	public function __set($attribute, $value)
	{
		if (array_key_exists($attribute, $this->attributes)) {
			return $this->attributes[$attribute] = $value;
		}

		throw new \Exception('Attribute [['.$attribute.']] does not exist.');
	}

	/**
	 * Resolve operator.
     * 
     * If both the value and operator are empty then return an equals operator. 
	 *
	 * @param string|null  $value the value of the condition.
	 * @param string|null  $operator the operator of the condition.
	 * @return string      $operator
	 */
	protected function resolveOperator($value, $operator)
	{
		if(empty($value) && empty($operator)) {
			return '=';
		}

		$operators = array_merge(
			Expression::$comparisonOperators,
			Expression::$wordOperators,
			Expression::$arrayableOperators
		);

		if (trim($operator) && !in_array($operator, $operators)) {
			throw new \Exception("Unrecognised operator [[$operator]]. Must be (".implode(', ', $operators).')');
		}

		return trim($operator);
	}

	/**
	 * Resolve values.
	 *
     * If both the value and operator are empty then return true. 
     *
	 * @param string   $value the value of the condition.
	 * @param string   $operator the operator of the condition.
	 * @return string  $value the modified value
	 */
	protected function resolveValue($value, $operator)
	{  
		if(empty($value) && empty($operator)) {
			return true;
		}

		if ($this->isArrayble($operator)) {
			$value = $this->asArray($value);
		}

		if (method_exists($this, ($method = 'operator'.ucfirst($operator)))) {
			return $this->{$method}($value);
		}

		return trim($value);
	}

	/**
	 * Check if operator exists.
	 *
	 * @param string|array $value
	 * @return boolean
	 */
	public function hasOperator($value)
	{
		return $this->attributeValueExists($value, 'operator');
	}

	/**
	 * Check if value exists.
	 *
	 * @param string|array $value
	 * @param string|null $key additionally check if value exists against a key.
	 * @return boolean
	 */
	public function hasValue($value, $key = null)
	{   
		if($key && !$this->hasKey($key)) {
			return false;
		}
		return $this->attributeValueExists($value, 'value');
	}

	/**
	 * Check if key exists.
	 *
	 * @param string|array $value
	 * @return boolean
	 */
	public function hasKey($value)
	{
		return $this->attributeValueExists($value, 'key');
	}

	/**
	 * Check if attribute value exists.
	 *
	 * @param string  $value
	 * @param string  $attribute
	 * @return string the modified value
	 */
	protected function attributeValueExists($value, $attribute)
	{
		if (!array_key_exists($attribute, $this->attributes)) {
			return false;
		}

		return count(array_intersect(
			array_map('strtolower', (array)$value), 
			array_map('strtolower', (array)$this->attributes[$attribute]))
		);
	}


	/**
	 * Operator in.
	 *
	 * @param array $value
	 * @return array
	 */
	protected function operatorIn(array $value)
	{
		if (empty($value)) {
			throw new \Exception('The in operator cannot be empty.');
		}
		return $value;
	}

	/**
	 * Operator between.
	 *
	 * @param string $value
	 * @return array
	 */
	protected function operatorBetween(array $value)
	{
		if (($total = count($value)) !== 2) {
			throw new \Exception('The between operator must have exactly two values. '.$total.' supplied.');
		}

		return $value;
	}

	/**
	 * Is arrayble.
	 *
	 * @param string $operator
	 * @return boolean
	 */
	protected function isArrayble($operator)
	{
		return in_array(trim($operator), Expression::$arrayableOperators);
	}

	/**
	 * String as array.
	 *
	 * @param string $value
	 * @param string $delimeter
	 * @return array $array
	 */
	protected function asArray($value, $delimeter = ',')
	{
		$array = explode($delimeter, trim($value, $delimeter));
		return array_filter($array, function($value){
			if(is_string($value) && !strlen($value)) {
				return false;
			}
			return true;
		});
	}

	/**
	 * Normalise condition.
	 *
	 * @param string $value
	 * @return array $array
	 */
	protected function normalise($value)
	{
		return preg_replace('/'.implode('|', Expression::$comparisonOperators).'/i', ' $0 ', $value);
	}

	/**
	 * Extract condition.
	 *
	 * @param string $condition
	 * @return array $array
	 */
	protected function extract($condition)
	{
		$condition = $this->normalise($condition);

		$portions = array_merge($this->asArray($condition, ' '), [null, null, null]);

		list($key, $operator) = array_values($portions);

		$value = trim(implode(' ', array_splice($portions, 2, count($portions))));

		return compact(['key', 'value', 'operator']);
	}

	/**
	 * Resolve conditions.
	 *
	 * @param string $condition
	 * @return Smrtr\Expression\Condition
	 */
	public function resolve($condition)
	{
		try {
			$extracted = $this->extract($condition);
		} catch (\Exception $e) {
			throw new BadlyFormattedConditionException($condition);
		}

		if(0 === strlen($extracted['value']) && $extracted['operator']) {
			throw new \Exception("Unable to resolve expression [[$condition]], value must not be empty if operator is set.");
		}

		$this->attributes['key'] = $extracted['key'];
		$this->attributes['operator'] = $this->resolveOperator($extracted['value'], $extracted['operator']);
		$this->attributes['value'] = $this->resolveValue($extracted['value'], $extracted['operator']);
		$this->attributes['original_value'] = $extracted['value'];

		return $this;
	}
}
