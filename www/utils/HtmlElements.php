<?php
/**
 * 
 *
 */
class HtmlElements {

	public static function htmlWithLanguage(): void {
		global $myLang;

		echo '<!doctype html public "-//W3C//DTD HTML 4.0 //EN">' . PHP_EOL;
		if ( isset($myLang) )
			echo '<html lang="' . $myLang . '">' . PHP_EOL;
		else
			echo '<html lang="ee">' . PHP_EOL;
	}
	
}
/**
 * @param string $in
 */
function filter_in($in): string {
	$s = filter_input(INPUT_GET, $in, FILTER_SANITIZE_STRING);
	$s1 = pg_escape_string($s);
	return(htmlspecialchars($s1));
}

/**
 * @param string $in
 */
function filter_inp($in): string {
	$s = filter_input(INPUT_POST, $in, FILTER_SANITIZE_STRING);
	$s1 = pg_escape_string($s);
	return(htmlspecialchars($s1));
}
