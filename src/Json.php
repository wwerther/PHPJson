<?php
  namespace wwerther\Json;
/**
*
* This is a Class that can be used instead of the stdClass-result generated by
* json_decode. It provides some advantages like the right arrow deep-dive into 
* the object.
* So it is possible to access deep properties using the syntax:
*   $json-object->level1->level2->level3
* It's also possible to create new proerties directly on a "deeper" level.
* Missing levels in between will be automatically generated.
*
* @TODO Fix the change-tracking functions for commit, rollback, haschanged
*       Therefore the getter and setter methods need to be adjusted
*
* @property All-properties can be accessed by name and will be automatically 
*          generated in the $data array. In case you access the property via
*           -> operator you will get a new Json-Object (in case the property
*           does not exists yet. In case you use the array operator a new array
*           is spawned in the $data section
*/
Class Json implements \ArrayAccess, \IteratorAggregate, \JsonSerializable {

  /** @var array Containing error-strings in case json_encode/decode failed */
  protected static $_messages = array(
        	JSON_ERROR_NONE => 'No error has occurred',
        	JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        	JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        	JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        	JSON_ERROR_SYNTAX => 'Syntax error',
        	JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    	);

  /** @var array Contain the real "properties" of the json-object, including subordinated json-objects */
  private $data=array();

  /** @var array Contains the original values (to track changes and allow rollback to last point of commit */
  private $orgvalues=array(
      'changed'=>array(),
      'deleted'=>array(),
      'created'=>array()
    );

  /** @var boolean Track if the object has changed since last "commit" or not */
  private $haschanged=false;

  /** @var int Contains the options for converting the object to a string. At the moment this is unused */
  private $options=JSON_PRETTY_PRINT;

  /**
  * Constructor of the object, allowing different inputs for creation
  *
  * @param mixed $json The data/information that should be transformed to a json-object. Depending on the input type this might be
  */
  public function __construct($json=null) {
    if (is_null($json)) return;
    if ($json instanceof Json) {
      $this->data=$json->data;
      return;
    }
    if (is_object($json)) {
      $stdClass=$json;
    } else {
      $stdClass=self::decode($json);
    }
    $this->cast($stdClass);
  }

  /**
  * Define JSON-Encoding-Options for this object
  * if Null
  */
  public function options($newoptions=null) {
    if (isset($newoptions)) {
      $this->options=$newoptions;
    }
    return $this->options;
  }
  
	/**
    * Copy properties of a given object to ourself
    * 
    * Deep dive into the given object and recast all properties
    * so at the end we either have "plain" values or Objects of Json-Class
    */
	private function cast(\stdClass $object) {
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

    /**
    * Commit changes to the object
    */
    public function commit() 
    {
      $this->orgvalues=array(
          'changed'=>array(),
          'deleted'=>array(),
          'created'=>array()
      );
      foreach ($this->data as $key=>$value) {
        if ($value instanceof JSON) {
          $value->commit();
        }
      }
      $this->haschanged=false;
    }

    /**
    * Rollback changes to the object to the last commit-point
    */
    public function rollback() 
    {
      foreach ($this->orgvalues['changed'] as $key=>$value) {
        $this->data[$key]=$value;
      }
      foreach ($this->orgvalues['deleted'] as $key=>$value) {
        $this->data[$key]=$value;
      }
      foreach ($this->orgvalues['created'] as $key) {
        unset($this->data[$key]);
      }
      $this->orgvalues=array(
          'changed'=>array(),
          'deleted'=>array(),
          'created'=>array()
      );
      foreach ($this->data as $key=>$value) {
        if ($value instanceof JSON) {
          $value->rollback();
        }
      }
      $this->haschanged=false;
    }

	public function ismodified() {
		foreach ($this->data as $key=>$value) {
			if ($value instanceof JSON) {
				$this->haschanged=$this->haschanged || $value->ismodified();
			}
		}	
		return $this->haschanged;
	}

// MAGIC METHODS for Right-Hand Property-Access
	
	public function __set($name,$value) {
	#	print "Setter for $name: $value\n";
		# Would be great to do a check whether or not the "new" value is the same like the old value
		# In that case I would keep haschanged to FALSE
		$this->data[$name]=$value;
		$this->haschanged=true;
	}
	
	/**
	* When called in Property Syntax we return an object, 
	* if called in Array syntax (offsetget) we return an array
	*/
	public function &__get($name) 
	{
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
      if (array_key_exists($name,$this->data)) {
        if (!array_key_exists($name,$this->orgvalues['deleted'])) $this->orgvalues['deleted'][$name]=$this->data[$name];
        unset($this->data[$name]);
        $this->haschanged=true;
      } 
    	}

// MAGIC METHODS for Right-Hand Array-Access
// INTERFACE ArrayAccess

    public function offsetSet($offset, $value) {
            # print "OS: $offset, ($value)\n";
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

    	public function &offsetGet($offset) {
            # print "OG: $offset\n";
	        if (isset($this->data[$offset])) {
              # print "Found\n";
              return $this->data[$offset];
            } else {
              # print "Create\n";
              $this->data[$offset]=array();
              return $this->data[$offset];
            };
  }

// INTERFACE ArrayIterator

  public function getIterator() {
    return new \ArrayIterator($this->data);
  }

// INTERFACE JsonSerializable

	/**
	*
	* @return An Array that could be parsed by JSON_Encode
	*/
	public function jsonSerialize() {
		$result=array();
		foreach ($this->data as $key=>$value) {
			if ($value instanceof self) {
				$result[$key]=$value->jsonSerialize();
		
			}elseif (is_array($value)) {
				$result[$key]=array_map(function($in) {
					if ($in instanceof self) {
						return $in->jsonSerialize();
					}
					return $in;
				},$value);
			} else {
				$result[$key]=$value;
			}
		}
		return $result;
	}


// Magic Method for getting a string-context of this object (using JSON-representation)

	/** 
	* Encode the Json-Object to a string
	*
	* Implicitly convert the current object to a string whenever called in a string-context
	*
	* @return string Containing the string-representation of the object
	*
	*/
	public function __tostring() {
		return self::encode($this,$this->options);
	}

// STATIC-METHODS


	/**
	* Encode Object to a Json-String (
	*
	* @param mixed $value
	* @param int $options JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT, JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE (default 0)
	* @param int $depth (default 512)
	*
	* @return string Containing a Json-String
	* @throws wwerther\Json\JsonException Containing the error that was created while encoding the object to a json-string
	*/
    public static function encode($value,$options = 0,$depth=512) {
      $result = json_encode($value, $options,$depth);

      if($result)  {
        return $result;
      }
      throw new JsonException(static::$_messages[json_last_error()]);
    }

    /**
    * Decode JSON-Text-String to Std-Object 
    *
    * This function takes a string and decodes it using json_decode. So it is a wrapper around the builtin-function.
    * It returns the value generated by json_decode in case the decoding was successful. Otherwise it throws an RuntimeException
    *
    * @param string $Json The Json-Text String to be parsed
    * @param boolean $assoc decode json as assoc string?
    *
    * @return stdClass returns an object containing the decoded json-value
    * @throws wwerther\Json\JsonException Containing the error that was created while deconding the json-file
    */
    public static function decode($json, $assoc = false) {
      $result = json_decode($json, $assoc);
      if($result) {
        return $result;
      }
      throw new JsonException(static::$_messages[json_last_error()]);
    }

	public static function lint($json) {
		#Seld\JsonLint\JsonParser
        	throw new \RuntimeException("Lint not implemented yet");
	}

}
