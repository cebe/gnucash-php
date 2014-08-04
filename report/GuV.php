<?php
/**
 * Created by PhpStorm.
 * User: cebe
 * Date: 04.08.14
 * Time: 19:07
 */

namespace cebe\gnucash\report;


use cebe\gnucash\entities\Account;
use SebastianBergmann\Money\Money;

class GuV
{
	public $excludeAccounts = array();

	/**
	 * @var Account[]
	 */
	private $_accounts;
	private $_startDate;
	private $_endDate;
	private $_currency;



	public function __construct($accounts, $startDate, $endDate, $currency)
	{
		$this->_accounts = $accounts;
		$this->_startDate = $startDate;
		$this->_endDate = $endDate;
		$this->_currency = $currency;
	}

	public function generate()
	{
		$income = array();
		$expense = array();
		$exclIncome = array();
		$exclExpense = array();
		$expenseSum = new Money(0, $this->_currency);
		$incomeSum = new Money(0, $this->_currency);
		$exclExpenseSum = new Money(0, $this->_currency);
		$exclIncomeSum = new Money(0, $this->_currency);
//		$sum = new Money(0, $this->_currency);
		foreach($this->_accounts as $account) {
			$var = null;
			if ($this->isIncome($account)) {
				if (in_array($account->id, $this->excludeAccounts)) {
					$var = 'exclIncome';
				} else {
					$var = 'income';
				}
			} elseif ($this->isExpense($account)) {
				if (in_array($account->id, $this->excludeAccounts)) {
					$var = 'exclExpense';
				} else {
					$var = 'expense';
				}
			}
			if (isset($var)) {
				$amount = $account->getAmount($this->_startDate, $this->_endDate);
				${$var}[$account->id] = $amount->negate();
				${$var . 'Sum'} = ${$var . 'Sum'}->add($amount->negate());
//				$sum = $sum->add($amount->negate());
			}
		}
		$expenseSum = $expenseSum->negate();
		$exclExpenseSum = $exclExpenseSum->negate();
		return compact(
			'income', 'expense', 'exclIncome', 'exclExpense',
			'incomeSum', 'expenseSum', 'exclIncomeSum', 'exclExpenseSum'
		);
	}


	public function isIncome($account)
	{
		switch($account->type)
		{
			case 'INCOME':
				return true;
		}
		return false;
	}

	public function isExpense($account)
	{
		switch($account->type)
		{
			case 'EXPENSE':
				return true;
		}
		return false;
	}
}