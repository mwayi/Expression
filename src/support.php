<?php

/**
 * Log to console
 *
 * @param string $text
 */
function pre_dump($text, $die = true) {
	echo "\n";
	print_r( $text );
	echo "\n\n";
	if($die) die;
}	