# Expression

A multi-purpose expression parser.

The expression parser allows developers to pass in a plain text statement that can be resolved to one of the following:- 

* An iterable conditions object
* A boolean (true or false) statement
* A MySql PDO object

Here is a sample use case:

	$expression = new \Smrtr\Expression('a = b');
	$conditions = $expression->toArray();
	print_r($conditions);

Would resolve to:

	Array
	(
		[0] => Smrtr\Expression\Condition Object
			(
				[attributes:protected] => Array
					(
						[key] => a
						[value] => b
						[original_value] => b
						[operator] => =
					)

			)

	)

You can now iterate the object recurrsively using a callback

	$result = $expression->solve(function($condition) {

		return $this->myCustomAssertionEngine(
			$condition->key,
			$condition->operator, 
			$condition->value
		);

	});

Where `$this->myCustomAssertionEngine` would be built to recognise keys and operators. The handlers would contain logic that would be able to return falsey and truthy results based upon the value supplied.
	
	protected function myCustomAssertionEngine($key, $operator, $value) 
	{
		$handler = 'handle' . ucfirst($key) . 'Key'; 

		if(method_exists($handler, $this)) {
			return $this->{$handler}($operator, $value);
		}

		return false;
	}

The result would be a boolean value.

	var_dump($result); // bool(true) or bool(false)


# Definitions

### Condition Elements
A condition consists of a key and value that are connected by an operator such as `=` that return true or false.
	
	[key] [operator] [value]
	  a        =        b

### Compound conditions
A compound condition combines two or more conditions that are connected by logical operators (for example, AND) that return true or false.

	a = b AND c = d

### Nested conditions
A nested condition uses parentheses to group conditions that are contained in another condition and are connected using AND and OR.

	a = b AND (c = d OR e = f)

### Logical operators
Logical operators combines two conditions together to return a set of results

	AND 
	OR

# Todo

- Allow condition elements `value` and `key` to be extended.
- Pass responsiblity of creating an expression object to an expression interface.

# Tests

	php vendor/bin/phpunit


