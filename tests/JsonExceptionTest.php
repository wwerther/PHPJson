<?php

use \wwerther\Json\JsonException;


class JsonExceptionTest extends PHPUnit_Framework_TestCase
{

	public function __construct() {
	}

	public function testValidJsonExceptionInit() {
		$this->setExpectedException('wwerther\Json\JsonException','Syntax error');
		throw new JsonException('Syntax error');	
	}

}
