<?php
//© 2019 Martin Peter Madsen
namespace MTM\Queues\Factories;

class Messages extends Base
{
	public function getSystemFive()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			if (extension_loaded("sysvmsg") === true) {
				$rObj	= new \MTM\Queues\Models\Message\SystemV\API();
				$this->_cStore[__FUNCTION__]	= $rObj;
			} else {
				throw new \Exception("sysvmsg extension not loaded");
			}
		}
		return $this->_cStore[__FUNCTION__];
	}
}