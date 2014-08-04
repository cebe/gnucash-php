<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace cebe\gnucash\entities;


class Book
{
	public $id;

	/**
	 * @var Slot[]
	 */
	public $slots = array();
	/**
	 * @var Account[]
	 */
	public $accounts = array();
	/**
	 * @var Transaction[]
	 */
	public $transactions = array();


} 