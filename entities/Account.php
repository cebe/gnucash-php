<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace cebe\gnucash\entities;


use SebastianBergmann\Money\Money;

class Account
{
	public $id;
	public $name;
	public $code;
	public $type;
	public $commodity;
	public $commodityScu;
	public $description;

	public $parent;
	public $children = array();

	/**
	 * @var TransactionSplit[]
	 */
	public $transactionSplits = array();

	protected $book;

	public function __construct($book)
	{
		$this->book = $book;
	}

	public function isRoot()
	{
		return $this->type === 'ROOT';
	}

	public function getAmount($startDate = null, $endDate = null) // TODO include children
	{
		if ($this->isRoot()) {
			throw new \Exception('The ROOT account does not have an amount.');
		}

		$amount = new Money(0, $this->commodity);

		foreach($this->transactionSplits as $split) {
			$date = strtotime($split->getTransaction()->datePosted);
			if ($startDate !== null && $date < $startDate) {
				continue;
			}
			if ($endDate !== null && $date > $endDate) {
				continue;
			}
			$amount = $amount->add($split->value);
		}
		return $amount;
	}

}