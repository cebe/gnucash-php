<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace cebe\gnucash\entities;


class Transaction
{
	public $id;
	public $currency;
	public $num;
	public $datePosted;
	public $dateEntered;
	public $description;

	/**
	 * @var TransactionSplit[]
	 */
	public $splits = array();

	protected $book;

	public function __construct($book)
	{
		$this->book = $book;
	}


	/*
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