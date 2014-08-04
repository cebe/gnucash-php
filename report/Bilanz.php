<?php
/**
 * Created by PhpStorm.
 * User: cebe
 * Date: 04.08.14
 * Time: 19:07
 */

namespace cebe\gnucash\report;


class Bilanz
{
	private $_accounts;

	public $excludeAccounts = array();



	public function __construct($accounts, $startDate, $endDate)
	{
		$this->_accounts = $accounts;
	}

	public function generate()
	{

	}


	public function isAktiva($account)
	{
		switch($account->type)
		{
			case 'ASSET':
			case 'BANK':
			case 'CASH':
				return 'aktiv';
			case 'LIABILITY':
				return 'passiv';
		}


	}
} 