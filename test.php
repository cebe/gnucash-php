<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

require(__DIR__ . '/GnuCash.php');
require(__DIR__ . '/entities/Book.php');
require(__DIR__ . '/entities/Slot.php');
require(__DIR__ . '/entities/Account.php');
require(__DIR__ . '/entities/Transaction.php');

$old = memory_get_usage();
$start = microtime(true);
// code that allocates memory (create objects etc...)

$gnucash = new \cebe\gnucash\GnuCash(__DIR__ . '/buchführung.gnucash');


$mem = memory_get_usage();
echo 'memory usage: '.(abs($mem - $old)/1024).' kb'."\n";
echo 'time: '.(microtime(true) - $start).' s'."\n";
