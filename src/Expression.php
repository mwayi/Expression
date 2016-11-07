<?php

namespace Smrtr\Expression;

use Closure;
use Smrtr\Expression\Condition;
use Smrtr\Expression\Exceptions\UnableToSolveExpressionException;

/**
 * 
 */
class Expression
{
	/**
	 * @var string
	 */
	protected $origin = null;

	/**
	 * @var integer
	 */
	protected $conditionCount = 0;

	/**
	 * @var integer
	 */
	protected $operatorCount = 0;

	/**
	 * @var string
	 */
	protected $normalised = null;

	/**
	 * @var array logical operators.
	 */
	public static $logicalOperators = [
		'and', 'or'
	];

	/**
	 * @var array arrayable operators.
	 */
	public static $arrayableOperators = [
        'in', 'between', 'between', 'notbetween'
    ];

	/**
	 * @var array comparison operators.
	 */
	public static $comparisonOperators = [
		'=', '!=', '>', '<', '<>', '><'
	];

	/**
	 * @var array comparison operators as words.
	 */
	public static $wordOperators = [
		'is', 'in', 'not', 'gt', 'lt', 'eq', 'like'
	];

	/**
	 * @var array replacements
	 */
	protected $replacements = [
		'/[\n\r\t]+/' => ' ',   // flatten input
		'/[\s]+/' => ' ',	   // one space only
		'/\s?\(\s?/' => '(',	// remove white space on opening
		'/\s?\)\s?/' => ')',	// remove white space on closing
		'/\s?([\,\:])\s?/' => '$1',	// ensure all commas separated values are cleared of whitespace
	];

	/**
	 * @var array the expression object
	 */
	protected $expObject = [];

	/**
	 * @param string $expression
	 */
	public function __construct($expression)
	{
		$this->origin = $expression;
	}

	/**
	 * Execute the expression.
	 * This will generate the expression object.
	 *
	 * @return Smrtr\Expression\Expression
	 */
	public function execute()
	{
		$this->expObject = $this->generateExpressionObjects($this->origin);

		if($this->conditionCount && $this->conditionCount - 1 !== $this->operatorCount) {
			throw new \Exception("Badly formatted expression. There aren't enough logical operators.");
		}

		return $this;
	}

	/**
	 * To array.
	 *
	 * @return array The expressions object
	 */
	public function toArray()
	{
		return $this->expObject;
	}

	/**
	 * Is enclosed group.
	 *
	 * @param  string $exp the expression.
	 * @param  string $open the opening boundary signature.
	 * @param  string $close the closing boundary signature.
	 * @return boolean
	 */
	protected function isEnclosed($exp, $open = '(', $close = ')')
	{
		return $exp = trim($exp) && (substr($exp, 0, 1) == $open && substr($exp, -1) == $close);
	}

	/**
	 * Normalise the expression.
	 *
	 * @param string $str normalise the expression string.
	 * @return string $normalised the normalised expression.
	 */
	protected function normalise($str)
	{
		foreach ($this->replacements as $regex => $replace) {
			$str = preg_replace($regex, $replace, $str);
		}

		return trim($str);
	}

	/**
	 * Resolve conditions.
	 *
	 * @param array $conditions
	 * @return array $pairings
	 */
	public function resolveConditions(array $conditions)
	{
		$pairings = [];
		foreach ($conditions as $condition) {

			if (!in_array(strtolower($condition), self::$logicalOperators)) {
				++$this->conditionCount;
				$pairings[] = new Condition($condition);
			} else {
				++$this->operatorCount;
				$pairings[] = $condition;
			}
		}
	
		return $pairings;
	}

	/**
	 * Generate the expression object.
	 *
	 * @param string $str the expression.
	 * @return array $expObject the expressions object.
	 */
	public function generateExpressionObjects($str)
	{
		$str = $this->unwrap($this->normalise($str));
		$store = [];
		foreach ($this->getRegionStrings($str) as $segment) {
			if ($this->isEnclosed($segment)) {
				$return = $this->generateExpressionObjects($segment);
				if (count($return) === 1) {
					$store[] = array_shift($return);
				} elseif (count($return) > 1) {
					$store[] = $return;
				}
			} else {
				$store = array_merge($store, $this->resolveConditions($this->resolveStatementSegments($segment)));
			}
		}
		
		if (in_array($operator = end($store), self::$logicalOperators)) {
			throw new \Exception("Expression [[$str]] cannot be terminated by [[$operator]].");
		}

		return $store;
	}

	/**
	 * Get Regions.
	 *
	 * @param string $exp the expression.
	 * @return array The key value indicies of where portions
	 *			   of the expression exist
	 */
	protected function getRegionIndexes($exp, $open = '(', $close = ')')
	{
		$regions = [];
		$last = null;
		foreach ($this->getEnclosureIndex($exp, $open, $close) as $opened => $closed) {
			if (is_null($last) || $last < $opened) {
				$regions[$opened] = $closed;
				$last = $closed;
			}
		}

		return $regions;
	}

	/**
	 * Get region strings.
	 *
	 * Ensure that a string such as (a + c)and t + b rsolves to:-
	 * ['a + c', 'and t + b']
	 *
	 * Regions are essentially boundaraires between parent parenthesis.
	 *
	 * @param  string $exp the compound expression to be segmented.
	 * @param  string $open the opening boundary signature.
	 * @param  string $close the closing boundary signature.
	 * @return array array of regions.
	 */
	public function getRegionStrings($exp, $open = '(', $close = ')')
	{
		$regions = $this->getRegionIndexes($exp, $open, $close);
		$length = strlen($exp);
		$chunks = [];
		$j = 0;

		for ($i = 0; $i < $length; ++$i) {
			$start = is_array($regions) && !empty($regions) ? current(array_flip($regions)) : $length;
			if (isset($regions[$i])) {
				$chunks[$j] = substr($exp, $i, $regions[$i] - $i + 1);
				$start = $regions[$i] + 1;  // Reset next pointer
				unset($regions[$i]);		// Pop the regions that have been consumed
			} else {
				$chunks[$j] = substr($exp, $i, $start - $i);
			}

			$i = $start - 1;

			++$j;
		}

		return $chunks;
	}

	/**
	 * Resolve statement segemets.
	 *
	 * @param string $exp the expression.
	 * @return array $segments.
	 */
	protected function resolveStatementSegments($exp)
	{
		$array = [];
		$i = 0;
		foreach (explode(' ', $exp) as $fragment) {
			if (in_array(strtolower($fragment), self::$logicalOperators)) {
				++$i;
				$array[$i] = $fragment;
				++$i;
			} else {
				if (! isset($array[$i])) {
					$array[$i] = null;
				}
				$array[$i] .= ' '.$fragment;
			}
		}

		return array_map(function ($val) {
			return trim($val);
		}, $array);
	}

	/**
	 * Recursively unwrap the expression.
	 *
	 * Ensure that ((((a + b)))) resolves to a + b
	 *
	 * @param  string $expression the expression
	 * @return string The unwrapped expression.
	 */
	protected function unwrap($expression)
	{
		$index = $this->getEnclosureIndex($expression);
		$continue = true;

		while ($continue) {
			if (isset($index[0]) && $index[0] === strlen($expression) - 1) {
				$expression = trim(substr(substr($expression, 1), 0, -1));
				$index = $this->getEnclosureIndex($expression);
				$continue = true;
			} else {
				$continue = false;
			}
		}

		return $expression;
	}

	/**
	 * Get Expression Index.
	 *
	 * @param  string $exp the expression.
	 * @param  string $open the opening boundary signature.
	 * @param  string $close the closing boundary signature.
	 * @return array  
	 */
	public function getEnclosureIndex($exp, $open = '(', $close = ')')
	{
		$length = strlen($exp);
		$opened = $index = [];

		for ($i = 0; $i < $length; ++$i) {
			if ($open === $exp[$i]) {
				$opened = array_merge([$i], $opened);
				$index[$i] = null;
			} elseif ($close === $exp[$i]) {
				$index[array_shift($opened)] = $i;
			}
		}

		if (substr_count($exp, $open) !== substr_count($exp, $close)) {
			throw new \Exception("There are unmatched tags on indexes inside [[$exp]]");
		}

		return $index;
	}

	/**
	 * Recurse through an array and act on it.
	 *
	 * @param Closure $callback to handle the expressions.
	 * @return string
	 */
	public function evaluate(Closure $callback)
	{   
		return $this->execute()->recurse($this->expObject, $callback, false);
	}

    /**
     * Recurse through an array and act on it.
     *
     * @param Closure $callback to handle the expressions.
     * @return boolean
     */
    public function solve(Closure $callback)
    {   
        $result = $this->execute()->recurse($this->expObject, $callback, true);

        return $this->solveExpression($result);
    }

	/**
	 * Solve expression.
	 *
	 * @param string $expression the expression to be solved.
	 * @return boolean the resulting expression value.
	 */
	protected function solveExpression($expression)
	{
		try {
			if(strlen(trim($expression))) {
				eval('$evaled = (boolean)(' . $expression. ');');
			}else $evaled = true;
		}
		catch(\Exception $e) {
			throw new UnableToSolveExpressionException;
		}
	   
		return $evaled;
	}

	/**
	 * Recursion factory.
	 *
	 * @param array $items
	 * @param closure $callback to evaluate conditions.
	 * @param boolean $evaluate 
     *              If true the expression will recurssively resolve to 'and', 'or' and true,
     *              false expressions, that subsequently provide a boolean answer.
     *              If false the expression will recurssively resolve to a new epxression string.
	 * @return string
	 */
	private function recurse($items, Closure $callback, $evaluate = false)
	{
		$return = null;
		foreach ($items as $index => $item) {
			if ($item instanceof Condition) {
				$result  = $callback($item, $index);
				$evaled  = $result? '1': '0';
				$return .= $evaluate? $evaled: $result;
			} elseif (is_string($item)) {
				$return .= ' ' . strtoupper($item) . ' ';
			} elseif (is_array($item)) {
				$return .= '('.$this->recurse($item, $callback, $evaluate) . ')';
			}
		}

		return $return;
	}

	/**
     * Get operators
     *
     * @return array $operators
     */
    public function getOperators()
    {   
    	return $this->operators;
    }

    /**
     * Add operator.
     *
     * @param string|array $item
     * @return boolean
     */
    public function addOperator($operator, array $config = null)
    {   
    	self::$wordOperators[strtolower($operator)] = strtolower($operator);
    }

    /**
     * Add logical operator
     *
     * @param string|array $item
     * @return boolean
     */
    public function addLogicalOperator($operator)
    {   
    	self::$logicalOperators[strtolower($operator)] = strtolower($operator);
    }

    /**
     * Add valid element values.
     *
     * @param string|array $item
     * @return boolean
     */
    public function mergeElementValues($item, $type)
    {   
        //$this->{$type}
        // remove
    }

    /**
     * Add valid element values.
     *
     * @param string|array $item
     * @return boolean
     */
    public function purgeElementValues($item)
    {   
        // remove
    }
}

