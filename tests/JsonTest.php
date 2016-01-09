<?php

use \wwerther\Json\Json;
use \PHPUnit_Framework_TestCase;


class JsonTest extends PHPUnit_Framework_TestCase
{
	protected $entity;

	protected $valid_json=<<<EOJSON
{
        "id":815,
        "type":"blub1",
        "test":{
                "id":4711,
                "type":"sub",
                "sub1":"valu",
                "_messages":"sflj",
                "arr1":["skd",{"test":{"dfd":"dsffff"}},"ldfh","fdh"]
                },
        "empt": null,
        "Arr2":["1","2","3"],
        "boo1": true,
        "boo2": false,
        "num": 1203
}
EOJSON;

	protected $invalid_json=<<<EOJSON
{[}
EOJSON;


	public function __construct() {
		$this->entity=new Json();
	}

	public function testEntityCreation() {
		$this->assertInstanceOf('wwerther\Json\Json',$this->entity);
	}
	
	public function testJsonToText() {
		$test=new Json();
		$this->assertEquals("".$test,'[]');
	}
	
	public function testJsonInit() {
		$ne=new Json($this->valid_json);
	}

	public function testInvalidJsonInit() {
		$this->setExpectedException('wwerther\Json\JsonException','Syntax error');
		$ne=new Json($this->invalid_json);
	}

}
