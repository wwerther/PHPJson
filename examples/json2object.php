<?php
require __DIR__ . '/../vendor/autoload.php';

use wwerther\Json\Json;

/*
 We use this as JSON-Input Data
*/
$json=<<<EOJSON
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

print $json;

/* Format to 'Pretty-printed' JSON by first decoding and afterwards encoding the string */
$d=Json::decode($json);
$d=Json::encode($d,JSON_PRETTY_PRINT);


/* Create an object from the Text-String */
$job=new Json($json);

/* Show the object */
var_dump($job);

/* Access to a nested array */
print_r ($job->test->arr1);

/* Change value within the array */
$job->test->arr1[1]->wert2="sdljs";

/* Attach another Json-Data to a new property */
$job->neu=new Json('{"sdjs":"dfj"}');
$job->eb1->eb2->eb3=null;
$job->eb1=array();
$job->zahl=42;
$job->text="Das ist ein Text";
$job->null=null;
$job->_messages="3wwdmswd";
$job->neu->_messages="3wwdmswd";
unset($job->empt);

print "\nORG:\n$d";
print "\nCONV:\n$job";

foreach ($job as $key=>$value) {
        print "$key:$value\n";
}
?>
