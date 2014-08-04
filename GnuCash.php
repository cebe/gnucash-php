<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace cebe\gnucash;

use cebe\gnucash\entities\Account;
use cebe\gnucash\entities\Book;
use cebe\gnucash\entities\Slot;
use cebe\gnucash\entities\Transaction;
use cebe\gnucash\entities\TransactionSplit;
use SebastianBergmann\Money\Currency;
use SebastianBergmann\Money\Money;

class GnuCash
{
	/**
	 * @var Book[]
	 */
	public $books = array();

	private $_currencies = array();

	private $_file;

	public function __construct($xmlFile)
	{
		$this->_file = $xmlFile;

		$cacheFile = dirname($xmlFile) . DIRECTORY_SEPARATOR . '.' . basename($xmlFile) . '.cache';
		$sha1 = sha1_file($xmlFile);
		if (file_exists($cacheFile) && $data = unserialize(file_get_contents($cacheFile))) {
			if ($sha1 === $data['sha1']) {
				$this->books = $data['books'];
				$this->postProcess();
				return;
			}
		}

		$this->books = $this->parseXml($xmlFile);

		$data = array(
			'sha1' => $sha1,
			'books' => $this->books,
		);
		file_put_contents($cacheFile, serialize($data));
		$this->postProcess();
	}

	/**
	 * @param string $xmlFile
	 * @return Book[]
	 * @throws \Exception
	 */
	protected function parseXml($xmlFile)
	{
		$parser = xml_parser_create('');

		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

	    if (!xml_parse_into_struct($parser, implode("", gzfile($xmlFile)), $elements)) {
		    xml_parser_free($parser);
		    throw new \Exception("failed to parse XML");
	    }
	    xml_parser_free($parser);

	    while($element = array_shift($elements)) {
		    if ($element['type'] === 'open' && $element['tag'] === 'gnc-v2') {
			    return $this->parseGNCv2($elements);
		    } else {
			    throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
		    }
	    }
		throw new \Exception('Unexpected end of xml file.');
	}

	// TODO temporary method to skip some parts of XML currently not needed
	protected function skipTag(&$elements, $tag, $level)
	{
		while ($element = array_shift($elements)) {
			if ($element['type'] === 'close' && $element['tag'] == $tag && $element['level'] == $level) {
				return;
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return Book[]
	 * @throws \Exception
	 */
	protected function parseGNCv2(&$elements)
	{
		$books = array();
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'gnc:count-data') {
			// TODO <gnc:count-data cd:type="book">1</gnc:count-data>

			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:book') {
				if ($element['attributes']['version'] == '2.0.0') {
					$book = $this->parseBook($elements);
					$books[$book->id] = $book;
				} else {
					throw new \Exception('Unsupported document version. Only 2.0.0 is supported.');
				}
			} elseif ($element['type'] === 'close') {
				return $books;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return Book
	 * @throws \Exception
	 */
	protected function parseBook(&$elements)
	{
		$book = new Book();
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'book:id') {
				$book->id = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'gnc:count-data') {
				// $book->id = $element['value']; // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'book:slots') {
				$book->slots = $this->parseSlots($elements);
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:commodity') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:GncCustomer') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:GncInvoice') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:GncTaxTable') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:account') {
				$account = $this->parseAccount($elements, $book);
				$book->accounts[$account->id] = $account;
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:transaction') {
				$transaction = $this->parseTransaction($elements, $book);
				$book->transactions[$transaction->id] = $transaction;
			} elseif ($element['type'] === 'close') {
				return $book;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return Slot[]
	 * @throws \Exception
	 */
	protected function parseSlots(&$elements)
	{
		$slots = array();
		while($element = array_shift($elements)) {
			if ($element['type'] === 'open' && $element['tag'] === 'slot') {
				$slots[] = $this->parseSlot($elements);
			} elseif ($element['type'] === 'close') {
				return $slots;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return Slot
	 * @throws \Exception
	 */
	protected function parseSlot(&$elements)
	{
		$slot = new Slot();
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'slot:key') {
				$slot->key = $element['value'];
			} elseif ($element['type'] === 'open' && $element['tag'] === 'slot:value' && $element['attributes']['type'] === 'frame') {
				$slot->value = $this->parseSlots($elements);
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'slot:value') {
				$slot->value = $element['value'];
			} elseif ($element['type'] === 'close') {
				return $slot;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return Account
	 * @throws \Exception
	 */
	protected function parseAccount(&$elements, $book)
	{
		$account = new Account($book);
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'act:name') {
				$account->name = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:id') { // TODO check type="guid"
				$account->id = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:type') {
				$account->type = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:code') {
				$account->code = $element['value'];
			} elseif ($element['type'] === 'open' && $element['tag'] === 'act:commodity') {
				$account->commodity = $this->parseCurrency($elements);
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:commodity-scu') {
				$account->commodityScu = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:non-standard-scu') {
				// $account->type = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:description') {
				$account->description = $element['value'];
			} elseif ($element['type'] === 'open' && $element['tag'] === 'act:slots') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:parent') { // TODO check type="guid"
				$account->parent = $element['value'];
			} elseif ($element['type'] === 'close') {
				return $account;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return Transaction
	 * @throws \Exception
	 */
	protected function parseTransaction(&$elements, $book)
	{
		$transaction = new Transaction($book);
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'trn:id') { // TODO check type="guid"
				$transaction->id = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'trn:num') {
				$transaction->num = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'trn:description') {
				$transaction->description = $element['value'];
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:currency') {
				$transaction->currency = $this->parseCurrency($elements);
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:date-posted') {
				$transaction->datePosted = $this->parseDate($elements);
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:date-entered') {
				$transaction->dateEntered = $this->parseDate($elements);
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:slots') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:splits') {
				$transaction->splits = $this->parseTransactionSplits($elements, $transaction);
			} elseif ($element['type'] === 'close') {
				return $transaction;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return string
	 * @throws \Exception
	 */
	protected function parseCurrency(&$elements)
	{
		$currencyCode = null;
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'cmdty:space') {
				if ($element['value'] !== 'ISO4217') {
					throw new \Exception('Only ISO4217 currency codes are supported. Found a ' . $element['value'] . ' value.');
				}
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'cmdty:id') {
				$currencyCode = $element['value'];
			} elseif ($element['type'] === 'close') {
				return $currencyCode;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}


	/**
	 * @param array $elements list of XML elements to parse.
	 * @return TransactionSplit[]
	 * @throws \Exception
	 */
	protected function parseTransactionSplits(&$elements, $transaction)
	{
		$splits = array();
		while($element = array_shift($elements)) {
			if ($element['type'] === 'open' && $element['tag'] === 'trn:split') {
				$splits[] = $this->parseTransactionSplit($elements, $transaction);
			} elseif ($element['type'] === 'close') {
				return $splits;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return TransactionSplit
	 * @throws \Exception
	 */
	protected function parseTransactionSplit(&$elements, $transaction)
	{
		$split = new TransactionSplit($transaction);
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'split:id') {
				$split->id = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'split:reconciled-state') {
				$split->reconciledState = $element['value'];
			} elseif ($element['type'] === 'open' && $element['tag'] === 'split:reconcile-date') {
				$split->reconciledDate = $this->parseDate($elements);
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'split:value') {
				$split->value = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'split:quantity') {
				$split->quantity = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'split:account') { // TODO check type="guid"
				$split->account = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'split:memo') {
				$split->memo = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'split:action') {
				$split->account = $element['value'];
			} elseif ($element['type'] === 'close') {
				return $split;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	/**
	 * @param array $elements list of XML elements to parse.
	 * @return string
	 * @throws \Exception
	 */
	protected function parseDate(&$elements)
	{
		$date = null;
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'ts:date') {
				$date = $element['value'];
			} elseif ($element['type'] === 'close') {
				return $date;
			} else {
				throw new \Exception('Unexpected xml tag: ' . print_r($element, true));
			}
		}
		throw new \Exception('Unexpected end of xml file.');
	}

	protected function postProcess()
	{
		foreach($this->books as $book) {
			foreach($book->accounts as $account) {
				if (!$account->isRoot()) {
					if (isset($book->accounts[$account->parent])) {
						$book->accounts[$account->parent]->children[$account->id] = $account;
						$account->parent = $book->accounts[$account->parent];
					} else {
						throw new \Exception('Account not found: ' . $account->parent);
					}
					if ($account->commodity === null) {
						throw new \Exception('Account without commodity: ' . $account->id);
					}
					$account->commodity = $this->getCurrency($account->commodity);
				}
			}
			foreach($book->transactions as $transaction) {
				foreach($transaction->splits as $split) {
					if (isset($book->accounts[$split->account])) {
						$book->accounts[$split->account]->transactionSplits[$split->id] = $split;
						$split->account = $book->accounts[$split->account];
					} else {
						throw new \Exception('Account not found: ' . $split->account);
					}
					$transaction->currency = $this->getCurrency($transaction->currency);
					$split->quantity = $this->string2money($split->quantity, $transaction->currency);
					$split->value = $this->string2money($split->value, $transaction->currency);
				}
			}
		}
	}

	/**
	 * @param $value
	 * @param Currency $currency
	 * @return Money
	 * @throws \Exception
	 */
	protected function string2money($value, $currency)
	{
		$parts = explode('/', $value);
		if (!preg_match('/^-?[0-9]+$/', $parts[0])) {
			throw new \Exception('Illegal currency value: ' . $value . ' for currency ' . $currency);
		}
		if (isset($parts[1])) {
			$div = (int) $parts[1];
			if ($div === $currency->getSubUnit()) {
				$moneyValue = (int) $parts[0];
			} elseif ($div < $currency->getSubUnit()) {
				$moneyValue = ((int) $parts[0]) * $currency->getSubUnit() / $div;
				if (!is_int($moneyValue)) {
					throw new \Exception('Illegal currency value: ' . $value . ' for currency ' . $currency . '. Value conversion resulted in a float value');
				}
			} else {
				throw new \Exception('Unknown currency value: ' . $value . ' for currency ' . $currency);
			}
		} else {
			throw new \Exception('Unknown currency value: ' . $value . ' for currency ' . $currency);
		}
		return new Money($moneyValue, $currency);
	}

	/**
	 * @param string $code
	 * @return Currency
	 */
	protected function getCurrency($code)
	{
		if ($code === null) {
			return null;
		} elseif ($code instanceof Currency) {
			return $code;
		}
		if (isset($this->_currencies[$code])) {
			return $this->_currencies[$code];
		}
		try {
			return $this->_currencies[$code] = new Currency($code);
		} catch (\Exception $e) {
			throw new \Exception("Unknown currency: '$code'.", $e->getCode(), $e);
		}
	}
}
