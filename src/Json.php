<?php namespace wwerther\Json;

Class Json implements \ArrayAccess, \IteratorAggregate {
	protected static $_messages = array(
        	JSON_ERROR_NONE => 'No error has occurred',
        	JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        	JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        	JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        	JSON_ERROR_SYNTAX => 'Syntax error',
        	JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    	);

	private $data=array();
	private $position=0;
	private $haschanged=false;

	public function __construct($json=null) {
        	$this->position = 0;
		if (is_null($json)) return;
		if ($json instanceof Json) {
			$this->data=$json->data;
			return;
		}
		if (is_object($json)) {
	#		print "construct: ".var_dump($json);
			$stdClass=$json;
		} else {
			$stdClass=self::decode($json);
		}
		$this->__cast($stdClass);		
	}

	private function __cast(\stdClass $object) {
        	if (is_array($object) || is_object($object)) {
            		foreach ($object as $key => $value) {
				# print "Key $key\n";
				if (is_object($value)) {
                			$this->data[$key] = new Json($value);
				} elseif (is_array($value)) {
				# print "$key\n";
					$this->data[$key]=array_map(function($in) {
								if (is_object($in)) {
									#print "Found \n".json_encode($in);	 
									return new Json($in);
								}
								return $in;
								},$value);

				} else {
					$this->data[$key] = $value;
				}
            		}
        	}
    	}

	public function commit() {
		$this->haschanged=false;
		foreach ($this->data as $key=>$value) {
			if ($value instanceof JSON) {
				#print "$key\n";
				$value->commit();
			}
		}	
	}

	public function ismodified() {
		foreach ($this->data as $key=>$value) {
			if ($value instanceof JSON) {
				$this->haschanged=$this->haschanged || $value->ismodified();
			}
		}	
		return $this->haschanged;
	}

	public function __set($name,$value) {
	#	print "Setter for $name: $value\n";
		# Would be great to do a check whether or not the "new" value is the same like the old value
		# In that case I would keep haschanged to FALSE
		$this->data[$name]=$value;
		$this->haschanged=true;
	}
	
	public function &__get($name) {
        	if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		$this->data[$name]=new Json();
		return $this->data[$name];
	}

	public function __isset($name) 
    	{
        #	echo "Ist '$name' gesetzt?\n";
        	return isset($this->data[$name]);
    	}
    
	public function __unset($name)
    	{
       # 	echo "Lösche '$name'\n";
        	unset($this->data[$name]);
		$this->haschanged=true;
    	}

	protected function __getassoc() {
		$result=array();
		foreach ($this->data as $key=>$value) {
			if ($value instanceof self) {
				$result[$key]=$value->__getassoc();
		
			}elseif (is_array($value)) {
				$result[$key]=array_map(function($in) {
					if ($in instanceof self) {
						return $in->__getassoc();
					}
					return $in;
				},$value);
			} else {
				$result[$key]=$value;
			}
		}
		return $result;
	}

	public function __tostring() {
		return self::encode($this->__getassoc(),JSON_PRETTY_PRINT);
	}

	public static function encode($value, $options = 0) {
        	$result = json_encode($value, $options);

        	if($result)  {
         	   	return $result;
        	}
        	throw new \RuntimeException(static::$_messages[json_last_error()]);
    	}

    	public static function decode($json, $assoc = false) {
        	$result = json_decode($json, $assoc);
        	if($result) {
            		return $result;
        	}
        	throw new \RuntimeException(static::$_messages[json_last_error()]);
    	}

	public static function lint($json) {
		#Seld\JsonLint\JsonParser
        	throw new \RuntimeException("Lint not implemented yet");
	}

	public function unique() {
		$this->data=array_unique($this->data);
		sort($this->data);
	}

	protected function data() {
		return $this->data;
	}
	
	public function push($data) {
		if ($data instanceof Json) {
			$this->data=array_merge($this->data,$data->data());
		} elseif (is_array($data)) {
			$this->data=array_merge($this->data,$data);
		} else {
			$this->data[]=$data;
		}
	}

    	public function offsetSet($offset, $value) {
        	if (is_null($offset)) {
            		$this->data[] = $value;
        	} else {
	    		# Would be great to do a check whether or not the "new" value is the same like the old value
	    		# In that case I would keep haschanged to FALSE
       			$this->data[$offset] = $value;
        	}
		$this->haschanged=true;
    	}

    	public function offsetExists($offset) {
	        return isset($this->data[$offset]);
    	}

    	public function offsetUnset($offset) {
        	unset($this->data[$offset]);
		$this->haschanged=true;
    	}

    	public function offsetGet($offset) {
	        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    	}

    	public function getIterator() {
    		return new \ArrayIterator($this->data);
    	}

};

?>
