<?php
/**
 * 
 * 
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace cebe\gnucash\entities;


class Account
{
	public $id;
	public $name;
	public $code;
	public $type;
	public $commodity;
	public $description;

	public $parent;

	protected $book;

	public function __construct($book)
	{
		$this->book = $book;
	}

} 