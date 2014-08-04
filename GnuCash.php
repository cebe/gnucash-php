<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace cebe\gnucash;


use SimpleXMLElement;
use yii\db\Exception;

class GnuCash
{
	public function __construct($xmlFile)
	{
		$xmlContent = implode("", gzfile($xmlFile));
		$this->parseXml($xmlContent);
	}

	protected function parseXml($xmlContent)
	{
//		print_r($xmlContent);
//		//$xml = new SimpleXMLElement($xmlContent);
//		libxml_use_internal_errors(true);
//		$xml = simplexml_load_string($xmlContent);
//		if (!$xml) {
//		    echo "Laden des XML fehlgeschlagen\n";
//		    foreach(libxml_get_errors() as $error) {
//		        echo "\t", $error->message;
//		    }
//		}
//		//$xml->children('book')
//
//		/** @var $element SimpleXMLElement */
//		foreach($xml as $element) {
//			print_r($element->getName());
//
//		}
//		return;


		$parser = xml_parser_create('');

		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

	    if (!xml_parse_into_struct($parser, trim($xmlContent), $elements)) {
		    xml_parser_free($parser);
		    throw new \Exception("failed to parse XML");
	    }
	    xml_parser_free($parser);

//		print_r($xmlValues);

//	    $xml_array = array ();
//	    $parents = array ();
//	    $opened_tags = array ();
//	    $arr = array ();
//	    $current = & $xml_array;
//	    $repeated_tag_index = array ();
	    while($element = array_shift($elements)) {
		    if ($element['type'] === 'open' && $element['level'] == 1 && $element['tag'] === 'gnc-v2') {

			    print_r($element);
			    $this->parseGNCv2($elements);
			    $this->parseCloseTag($elements);

		    } else {
			    throw new \Exception('unexpected xml tag: ' . print_r($element, true));
		    }

	    }


	}

	protected function parseCloseTag(&$elements)
	{
		if ($element = array_shift($elements)) {
			if ($element['type'] === 'close') {
				return;
			}
		}
		throw new \Exception('expected close tag but got: ' . print_r($element, true));
	}

	protected function skipTag(&$elements, $tag, $level)
	{
		while ($element = array_shift($elements)) {
			if ($element['type'] === 'close' && $element['tag'] == $tag && $element['level'] == $level) {
				return;
			}
		}
		throw new \Exception('expected close tag but got: ' . print_r($element, true));
	}


	protected function parseGNCv2(&$elements)
	{

		while($element = array_shift($elements)) {
			print_r($element);
			if ($element['type'] === 'complete' && $element['tag'] === 'gnc:count-data') {
			// TODO <gnc:count-data cd:type="book">1</gnc:count-data>

			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:book') {
				if ($element['attributes']['version'] == '2.0.0') {
					$this->parseBook($elements);
//					$this->parseCloseTag($elements);
				} else {
					throw new \Exception('unsupported version');
				}
			} else {
				throw new \Exception('unexpected xml tag: ' . print_r($element, true));
			}
		}
	}

	protected function parseBook(&$elements)
	{
		$book = new Book();
		while($element = array_shift($elements)) {
			print_r($element);
			if ($element['type'] === 'complete' && $element['tag'] === 'book:id') {
				$book->id = $element['value'];
			} elseif ($element['type'] === 'open' && $element['tag'] === 'book:slots') {
				$book->slots = $this->parseSlots($elements);
//				$this->parseCloseTag($elements);
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:commodity') {
				$this->skipTag($elements, $element['tag'], $element['level']); // TODO
			} elseif ($element['type'] === 'open' && $element['tag'] === 'gnc:account') {
				$account = $this->parseAccount($elements);
				$book->accounts[$account->id] = $account;
//				$this->parseCloseTag($elements);
			} elseif ($element['type'] === 'close') {
				return $book;
			}
		}
		throw new \Exception('unexpected xml tag: ' . print_r($element, true));
	}

	protected function parseSlots(&$elements)
	{
		$slots = [];
		while($element = array_shift($elements)) {
			print_r($element);
			if ($element['type'] === 'open' && $element['tag'] === 'slot') {
				$slots[] = $this->parseSlot($elements);
//				$this->parseCloseTag($elements);
			} elseif ($element['type'] === 'close') {
				return $slots;
			}
		}
		throw new \Exception('unexpected xml tag: ' . print_r($element, true));
	}

	protected function parseSlot(&$elements)
	{
		$slot = new Slot();
		while($element = array_shift($elements)) {
			print_r($element);
			if ($element['type'] === 'complete' && $element['tag'] === 'slot:key') {
				$slot->key = $element['value'];
			} elseif ($element['type'] === 'open' && $element['tag'] === 'slot:value' && $element['attributes']['type'] === 'frame') {
				$slot->value = $this->parseSlots($elements);
//				$this->parseCloseTag($elements);
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'slot:value') {
				$slot->value = $element['value'];
			} elseif ($element['type'] === 'close') {
				return $slot;
			}
		}
		throw new \Exception('unexpected xml tag: ' . print_r($element, true));
	}

//	protected function parseSlotValue(&$elements)
//	{
//		while($element = array_shift($elements)) {
//			print_r($element);
//			if ($element['type'] === 'complete' && $element['tag'] === 'slot:key') {
////				$slot->key = $element['value'];
//			} elseif ($element['type'] === 'open' && $element['tag'] === 'slot:value') {
////				$slot->value = $element['value'];
//			} else {
//				throw new \Exception('unexpected xml tag: ' . print_r($element, true));
//			}
//		}
//	}

	protected function parseAccount(&$elements)
	{
		$account = new Account();
		while($element = array_shift($elements)) {
			print_r($element);
			if ($element['type'] === 'complete' && $element['tag'] === 'act:name') {
				$account->name = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:id') { // TODO check type="guid"
				$account->id = $element['value'];
			} elseif ($element['type'] === 'complete' && $element['tag'] === 'act:type') {
				$account->type = $element['value'];
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
			}
		}
		throw new \Exception('unexpected xml tag: ' . print_r($element, true));
	}

	protected function parseTransaction(&$elements)
	{
		$transaction = new Transaction();
		while($element = array_shift($elements)) {
			print_r($element);
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
			}
		}
		throw new \Exception('unexpected xml tag: ' . print_r($element, true));
	}

	protected function parseDate(&$elements)
	{
		$date = null;
		while($element = array_shift($elements)) {
			if ($element['type'] === 'complete' && $element['tag'] === 'ts:date') {
				$date = $element['value'];
			} elseif ($element['type'] === 'close') {
				return $date;
			}
		}
		throw new \Exception('unexpected xml tag: ' . print_r($element, true));
	}

	/*<gnc:transaction version="2.0.0">
	  <trn:id type="guid">d041066415b3230b4411a074cc2a03fa</trn:id>
	  <trn:currency>
	    <cmdty:space>ISO4217</cmdty:space>
	    <cmdty:id>EUR</cmdty:id>
	  </trn:currency>
	  <trn:num>12</trn:num>
	  <trn:date-posted>
	    <ts:date>2013-04-29 00:00:00 +0200</ts:date>
	  </trn:date-posted>
	  <trn:date-entered>
	    <ts:date>2013-09-03 10:08:04 +0200</ts:date>
	  </trn:date-entered>
	  <trn:description>Lastschrift Domainoffensive SSL</trn:description>
	  <trn:slots>
	    <slot>
	      <slot:key>date-posted</slot:key>
	      <slot:value type="gdate">
	        <gdate>2013-04-29</gdate>
	      </slot:value>
	    </slot>
	  </trn:slots>
	  <trn:splits>
	    <trn:split>
	      <split:id type="guid">e853ee9927ffd95282d390d850a7f53e</split:id>
	      <split:reconciled-state>c</split:reconciled-state>
	      <split:value>16723/100</split:value>
	      <split:quantity>16723/100</split:quantity>
	      <split:account type="guid">136aca0305ac1deafccdbdd3ca4e5d04</split:account>
	    </trn:split>
	    <trn:split>
	      <split:id type="guid">9b09a7af304a88c390f269e6041ca5ac</split:id>
	      <split:reconciled-state>n</split:reconciled-state>
	      <split:value>3177/100</split:value>
	      <split:quantity>3177/100</split:quantity>
	      <split:account type="guid">a1142a03151b1d1bc97b743b50ce4e8b</split:account>
	    </trn:split>
	    <trn:split>
	      <split:id type="guid">e8a7879e004926c161bcad895d0d64be</split:id>
	      <split:reconciled-state>c</split:reconciled-state>
	      <split:value>-19900/100</split:value>
	      <split:quantity>-19900/100</split:quantity>
	      <split:account type="guid">8cf3fe2596b95333b441acde26a6cd32</split:account>
	    </trn:split>
	  </trn:splits>
	</gnc:transaction>*/


}