<?php

namespace Smrtr\Expression\Adaptors;

use PDO;

class Mysql
{
	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * @var string
	 */
	protected $where;

	/**
	 * Array of MySQL functions.
	 *
	 * @var array
	 */
	protected static $reservedFunctions = ['ABS(', 'ACOS(', 'ADDDATE(', 'ADDTIME(', 'AES_DECRYPT(', 'AES_ENCRYPT(', 'ASCII(', 'ASIN(', 'ATAN(', 'ATAN2(', 'AVG(', 'BENCHMARK(', 'BIN(', 'BIT_AND(', 'BIT_COUNT(', 'BIT_LENGTH(', 'BIT_OR(', 'BIT_XOR(', 'CAST(', 'CEIL(', 'CEILING(', 'CHAR(', 'CHAR_LENGTH(', 'CHARACTER_LENGTH(', 'CHARSET(', 'COALESCE(', 'COERCIBILITY(', 'COLLATION(', 'COMPRESS(', 'CONCAT(', 'CONCAT_WS(', 'CONNECTION_ID(', 'CONV(', 'CONVERT(', 'CONVERT_TZ(', 'COS(', 'COT(', 'COUNT(', 'COUNT(DISTINCT', 'CRC32(', 'CURDATE(', 'CURRENT_DATE(', 'CURRENT_TIME(', 'CURRENT_TIMESTAMP(', 'CURRENT_USER(', 'CURTIME(', 'DATABASE(', 'DATE(', 'DATE_ADD(', 'DATE_FORMAT(', 'DATE_SUB(', 'DATEDIFF(', 'DAY(', 'DAYNAME(', 'DAYOFMONTH(', 'DAYOFWEEK(', 'DAYOFYEAR(', 'DECODE(', 'DEFAULT(', 'DEGREES(', 'DES_DECRYPT(', 'DES_ENCRYPT(', 'ELT(', 'ENCODE(', 'ENCRYPT(', 'EXP(', 'EXPORT_SET(', 'EXTRACT(', 'FIELD(', 'FIND_IN_SET(', 'FLOOR(', 'FORMAT(', 'FOUND_ROWS(', 'FROM_DAYS(', 'FROM_UNIXTIME(', 'GET_FORMAT(', 'GET_LOCK(', 'GREATEST(', 'GROUP_CONCAT(', 'HEX(', 'HOUR(', 'IF(', 'IFNULL(', 'IN(', 'INET_ATON(', 'INET_NTOA(', 'INSERT(', 'INSTR(', 'INTERVAL(', 'IS_FREE_LOCK(', 'IS_USED_LOCK(', 'ISNULL(', 'LAST_INSERT_ID(', 'LCASE(', 'LEAST(', 'LEFT(', 'LENGTH(', 'LN(', 'LOAD_FILE(', 'LOCALTIME(', 'LOCALTIMESTAMP(', 'LOCATE(', 'LOG(', 'LOG10(', 'LOG2(', 'LOWER(', 'LPAD(', 'LTRIM(', 'MAKE_SET(', 'MAKEDATE(', 'MASTER_POS_WAIT(', 'MAX(', 'MD5(', 'MICROSECOND(', 'MID(', 'MIN(', 'MINUTE(', 'MOD(', 'MONTH(', 'MONTHNAME(', 'NAME_CONST(', 'NOT IN(', 'NOW(', 'NULLIF(', 'OCT(', 'OCTET_LENGTH(', 'OLD_PASSWORD(', 'ORD(', 'PASSWORD(', 'PERIOD_ADD(', 'PERIOD_DIFF(', 'PI(', 'POSITION(', 'POW(', 'POWER(', 'PROCEDURE ANALYSE(', 'QUARTER(', 'QUOTE(', 'RADIANS(', 'RAND(', 'RELEASE_LOCK(', 'REPEAT(', 'REPLACE(', 'REVERSE(', 'RIGHT(', 'ROUND(', 'ROW_COUNT(', 'RPAD(', 'RTRIM(', 'SCHEMA(', 'SEC_TO_TIME(', 'SECOND(', 'SESSION_USER(', 'SHA(', 'SHA1(', 'SIGN(', 'SIN(', 'SLEEP(', 'SOUNDEX(', 'SPACE(', 'SQRT(', 'STD(', 'STDDEV(', 'STDDEV_POP(', 'STDDEV_SAMP(', 'STR_TO_DATE(', 'STRCMP(', 'SUBDATE(', 'SUBSTR(', 'SUBSTRING(', 'SUBSTRING_INDEX(', 'SUBTIME(', 'SUM(', 'SYSDATE(', 'SYSTEM_USER(', 'TAN(', 'TIME(', 'TIME_FORMAT(', 'TIME_TO_SEC(', 'TIMEDIFF(', 'TIMESTAMP(', 'TIMESTAMPADD(', 'TIMESTAMPDIFF(', 'TO_DAYS(', 'TRIM(', 'TRUNCATE(', 'UCASE(', 'UNCOMPRESS(', 'UNCOMPRESSED_LENGTH(', 'UNHEX(', 'UNIX_TIMESTAMP(', 'UPPER(', 'USER(', 'UTC_DATE(', 'UTC_TIME(', 'UTC_TIMESTAMP(', 'UUID(', 'VALUES(', 'VAR_POP(', 'VAR_SAMP(', 'VARIANCE(', 'VERSION(', 'WEEK(', 'WEEKDAY(', 'WEEKOFYEAR(', 'YEAR(', 'YEARWEEK('];

	public function __construct(Condition $condition)
	{
		$this->condition = $condition;
		$this->where = $this->generate($condition);
	}

	/**
	 * Generat the where condition.
	 *
	 * @param \Smrtr\Expression\Condition
	 * @return string
	 */
	protected function generate(Condition $condition)
	{
		$where = null;

		// Is nexted
		$operator = strtoupper($condition->operator);
		$value = $condition->value;
		$field = $condition->key;
		$this->extractParameters($field, $value);

		if (is_array($value) && count($value)) {
			$array = [];

			foreach ($value as $val) {
				if ($this->isExpression($val)) {
					$array[] = $this->getExpression($val);
				} else {
					$this->extractParameters($field, $val);
					$array[] = '?';
				}
			}

			if ($operator === 'BETWEEN') {
				$where = "$field $operator ".implode(' AND ', $array);
			} else {
				if ($operator === '!=') {
					$operator = 'NOT';
				}

				if ($operator !== 'NOT') {
					$operator = null;
				}

				$where = "$field $operator IN(".implode(', ', $array).')';
			}
		} else {
			if (is_null($value)) {
				if ($operator === '!=') {
					$operator = 'NOT';
				}

				if ($operator !== 'NOT') {
					$operator = null;
				}

				$where = "$field IS $operator NULL";
			} elseif ($field === 'EXISTS' || $field === 'NOT EXISTS') {
				$where = "$field ($value)";
			} else {
				$where = "$field $operator ?";
				if ($this->isExpression($value)) {
					$where = "$field ".$this->getExpression($value);
				} elseif ($this->isFunction($value)) {
					$where = "$field $operator $value";
				}
			}
		}

		return $where;
	}

	/**
	 * Extract parameters and place them for later use.
	 *
	 * @param string $field
	 * @param string $value
	 * @return void
	 */
	public function extractParameters($field, $value)
	{
		$parameter = (object)[
			'field' => $field, 
			'value' => $value, 
			'type'  => PDO::PARAM_STR
		];
	   
		if (!is_array($value)) {
			if (is_numeric($value)) {
				$parameter->type = PDO::PARAM_INT;
			}
			if ($this->isTokenisedParameter($value)) {
				$this->parameters[] = $parameter;
			}
		}
	}

	/**
	 * Is tokenised expression.
	 *
	 * @param string $value
	 * @return boolean
	 */
	protected function isTokenisedParameter($value)
	{
		return !$this->isExpression($value) && !is_null($value) && !$this->isFunction($value);
	}

	/**
	 * Encapsulate as expression.
	 *
	 * Unescapes string so that you can put expressions such as
	 *
	 *  a.id = 'a.parent' => a.id = a.parent
	 *
	 * @param mixed $input Input to escape
	 * @return string
	 */
	public function expression($input)
	{
		return '`'.trim($input, '`').'`';
	}

	/**
	 * Get Expression.
	 *
	 * @param mixed $input to escape.
	 * @return string
	 */
	protected function getExpression($input)
	{
		return trim($input, '`');
	}

	/**
	 * Check if Mysql Function.
	 *
	 * @param mixed $input to check.
	 * @return string|bool
	 */
	protected function isFunction($input)
	{
		return ($temp = substr($input, 0, strpos($input, '('))) && preg_grep('/^'.preg_quote(strtoupper($temp), '/').'/i', self::$reservedFunctions);
	}

	/**
	 * Check if the Mysql input is actually an expression.
	 *
	 * @param mixed $input Input to check
	 * @return string|bool
	 */
	protected function isExpression($input)
	{
		return substr($input, strlen($input) - 1, 1) === '`' && substr($input, 0, 1) === '`';
	}

	/**
	 * Get the genereated where clause.
	 *
	 * @return string
	 */
	public function where()
	{
		return $this->where;
	}

	/**
	 * Get the tokenised parameters.
	 *
	 * @return array of the parameter values.
	 */
	public function parameters()
	{
		return array_map(
			function($parameter){
				return $parameter->value;
			}, $this->parameters
		);
	}
}
