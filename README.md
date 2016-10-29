# Expression

A multi-purpose expression parser.

The expression parser allows developers to pass in a plain text statement that can be resolved to:- 

a) An iterable conditions object
b) A boolean (true or false) statement
c) A MySql PDO object


Here is a sample use case:

	new \Smrtr\Exp\Parse('a = b and (a = c and a = d)');

	new \Smrtr\Expression\Parse('a = b and (a = c and a = d)');
	
Resolves to 



### Condition Elements
A condition consists of an attribute and value that are connected by an operator (such as =) that returns true or false.

Attribute

Operator


### Compound conditions
A compound condition combines two or more conditions that are connected by logical operators (for example, AND) that return true or false.

### Nested conditions
A nested condition uses parentheses to group conditions that are contained in another condition and are connected using AND and OR.

### Logical operators
Logical operators combines two conditions together to return a set of results