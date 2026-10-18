<?php
// Teardown-time zend_bailout: the handler returns normally, then an open output-buffer callback fatals during php_output_end_all() inside rapira_request_teardown(), which the per-call zend_try in that C helper must contain.
// display_errors=1 keeps php_error_cb from setting the 500 itself (main/main.c): the status must come from the seal. The fatal text lands in the dying buffer and never reaches ub_write.
ini_set('display_errors', '1');

class Counter
{
    public static int $n = 0;
}

$handler = static function (): void {
    Counter::$n++;
    $boom = $_GET['boom'] ?? '';
    if ($boom === '2') {
        echo 'part'; // reaches ub_write before the buffer opens: the 200 head is committed
    }
    if ($boom === '1' || $boom === '2') {
        // the callback runs at teardown, when php_output_end_all force-pops the buffer, not during the handler
        ob_start(static function (string $buf): string {
            trigger_error('boom during output flush', E_USER_ERROR); // E_USER_ERROR -> zend_bailout
            return $buf;
        });
        echo "never flushes cleanly";            // buffered; never makes it to ub_write
        return;
    }
    header('Content-Type: text/plain');
    echo "ok counter=" . Counter::$n;
};

while (\Rapira\handle_request($handler)) {
    gc_collect_cycles();
}
