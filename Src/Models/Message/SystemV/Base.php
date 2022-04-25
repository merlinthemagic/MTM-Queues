<?php
//� 2019 Martin Peter Madsen
namespace MTM\Queues\Models\Message\SystemV;

class Base
{
	protected $_parentObj=null;
	
	public function setParent($obj)
	{
		$this->_parentObj	= $obj;
		return $this;
	}
	public function getParent()
	{
		return $this->_parentObj;
	}
}