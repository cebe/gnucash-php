<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

require(__DIR__ . '/GnuCash.php');
require(__DIR__ . '/Book.php');
require(__DIR__ . '/Slot.php');
require(__DIR__ . '/Account.php');

$gnucash = new \cebe\gnucash\GnuCash(__DIR__ . '/buchführung.gnucash');
$gnucash = new \cebe\gnucash\GnuCash(__DIR__ . '/buchführung.gnucash.unzip');

