<?php
//© 2019 Martin Peter Madsen
namespace MTM\NewDefault\Factories;

class Queues extends Base
{
	public function getSemaphore()
	{
		echo "\n <code><pre> \nClass:  ".get_class($this)." \nMethod:  ".__FUNCTION__. "  \n";
		//var_dump($_SERVER);
		echo "\n 2222 \n";
		//print_r($_GET);
		echo "\n 3333 \n";
		print_r(get_loaded_extensions());
		echo "\n ".time()."</pre></code> \n ";
		die("end");
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			if (extension_loaded("parallel") === true) {
				$rObj	= new \MTM\Async\Models\Threading\Parallel\Api();
				$this->_cStore[__FUNCTION__]	= $rObj;
			} else {
				throw new \Exception("Parallel extension not loaded");
			}
		}
		return $this->_cStore[__FUNCTION__];
	}
	public function getSemaphore()
	{
		if (array_key_exists(__FUNCTION__, $this->_cStore) === false) {
			$this->_cStore[__FUNCTION__]	= new \stdClass();
		}
		return $this->_cStore[__FUNCTION__];
	}
}