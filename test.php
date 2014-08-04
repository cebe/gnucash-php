<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

use SebastianBergmann\Money\IntlFormatter;

require(__DIR__ . '/vendor/autoload.php');

$old = memory_get_usage();
$start = microtime(true);
// code that allocates memory (create objects etc...)

$gnucash = new \cebe\gnucash\GnuCash(__DIR__ . '/buchführung.gnucash');


$mem = memory_get_usage();
echo 'memory usage: '.(abs($mem - $old)/1024).' kb'."\n";
echo 'time: '.(microtime(true) - $start).' s'."\n";

$f = new IntlFormatter('de_DE');
foreach($gnucash->books as $book) {
	foreach($book->accounts as $account) {
		if ($account->isRoot()) {
			continue;
		}
		echo $account->name . ': ' . $f->format($account->getAmount()) . "\n";
	}
}