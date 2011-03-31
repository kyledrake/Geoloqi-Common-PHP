<?php
/**
 * http://github.com/tylerhall/simple-php-framework/blob/master/includes/class.loop.php
 */
class Cycler {
	private $_index;
	private $_elements;
	private $_numElements;
	
	public function __construct() {
		$this->_index = 0;
		$this->_elements = func_get_args();
		$this->_numElements = func_num_args();
	}
	
	public function __toString() {
		return (string)$this->get();
	}
	
	public function reset() {
		$this->_index = 0;
	}
	
	public function get() {
		if($this->_numElements == 0)
			return null;
		
		$val = $this->_elements[$this->_index++];
		
		$this->_index = $this->_index % $this->_numElements;
		
		return $val;
	}
}