<?php
//© 2019 Martin Peter Madsen
namespace MTM\Queues;

class Factories
{
	private static $_cStore=array();
	
	//USE: $aFact		= \MTM\Queues\Factories::$METHOD_NAME();
	
	public static function getMessages()
	{
		if (array_key_exists(__FUNCTION__, self::$_cStore) === false) {
			self::$_cStore[__FUNCTION__]	= new \MTM\Queues\Factories\Messages();
		}
		return self::$_cStore[__FUNCTION__];
	}
}