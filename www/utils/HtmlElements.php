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