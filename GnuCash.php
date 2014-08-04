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

class GnuCash
{
	public $books = [];

	private $_file;

	public function __construct($xmlFile)
	{
		$this->_file = $xmlFile;
		$this->parseXml($xmlFile);
	}

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
			    $this->parseGNCv2($elements);
			    return;
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
	 * @throws \Exception
	 */
	protected function parseGNCv2(&$elements)
	{

		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'gnc:count-data') {
			// TODO <gnc:count-data cd:type="book">1</gnc:count-data>

			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:book') {
				if ($element['attributes']['version'] == '2.0.0') {
					$book = $this->parseBook($elements);
					$this->books[$book->id] = $book;
				} else {
					throw new \Exception('Unsupported document version. Only 2.0.0 is supported.');
				}
			} elseif ($element['type'] === 'close') {
				return;
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
		$slots = [];
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
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:commodity-scu') {
				// TODO
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
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:date-posted') {
				$transaction->datePosted = $this->parseDate($elements);
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:date-entered') {
				$transaction->dateEntered = $this->parseDate($elements);
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:slots') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'trn:splits') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
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
}
