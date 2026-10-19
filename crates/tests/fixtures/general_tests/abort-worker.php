<?php
class Track
{
	public static int $done = 0;
}

$handler = static function (): void {
	header('Content-Type: text/plain');
	if (($_GET['probe'] ?? '') === '1') {
		// (int): php-src master returns bool, PHP 8 returns int. https://github.com/php/php-src/commit/8639e7522a33309b1ed9d096c4a90204e2e50d1a
		echo "done=", Track::$done, " aborted=", (int) connection_aborted();
		return;
	}
	// the test drops the receiver after 'held'; the sleep lets the drop land before the first write
	\Rapira\log('held');
	usleep(300000);
	echo "payload\n"; // aborted write: the SAPI raises php_handle_aborted_connection
	Track::$done++; // must not run when the client disconnected
};
while (\Rapira\handle_request($handler)) {
	gc_collect_cycles();
}
