<?php

use \wwerther\Json\Json;

class JsonTest extends PHPUnit_Framework_TestCase
{

	public function __construct() {

	}

	public function testJsonInit() {
#		$this->setExpectedException('RuntimeError');
		$ne=new Json();
	}

}
