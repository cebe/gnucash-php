<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace cebe\gnucash\entities;


class TransactionSplit
{
	public $id;
	public $currency;
	public $num;
	public $datePosted;
	public $dateEntered;
	public $description;


	protected $book;

	public function __construct($book)
	{
		$this->book = $book;
	}


	/*  <trn:splits>
    <trn:split>
      <split:id type="guid">8ce34328f52b2c3a6530b2bb96ac8849</split:id>
      <split:reconciled-state>n</split:reconciled-state>
      <split:value>9998/100</split:value>
      <split:quantity>9998/100</split:quantity>
      <split:account type="guid">accbd1ed250265f04afc258cb943c284</split:account>
    </trn:split>
    <trn:split>
      <split:id type="guid">59f56473d1735c5579e185facf833915</split:id>
      <split:reconciled-state>n</split:reconciled-state>
      <split:value>-9998/100</split:value>
      <split:quantity>-9998/100</split:quantity>
      <split:account type="guid">a1142a03151b1d1bc97b743b50ce4e8b</split:account>
    </trn:split>
  </trn:splits>*/
} 