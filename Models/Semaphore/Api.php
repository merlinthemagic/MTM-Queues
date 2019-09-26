<?php
//© 2019 Martin Peter Madsen
namespace MTM\Async\Models\Threading\Parallel;

class Api
{
	protected $_queueObjs=array();
	protected $_channelObjs=array();
	
	public function getNewQueue($id=null)
	{
		$rObj	= new \MTM\Async\Models\Threading\Parallel\Thread();
		$rObj->setParent($this)->setBootStrap($bootStrap);
		$this->_queueObjs[$rObj->getGuid()]	= $rObj;
		return $rObj;
	}
	public function getQueues()
	{
		return array_values($this->_queueObjs);
	}
	protected function queueExists($id, $throw=true)
	{
		if (msg_queue_exists($id) === true) {
			return true;
		} elseif ($throw === true) {
			throw new \Exception("Queue does not exist: " . $id);
		} else {
			return false;
		}
	}
	protected function addQueue($id)
	{
		if ($this->queueExists($id, false) === false) {
			$qRes	= msg_get_queue($id, intval("0600", 8));
			if (is_resource($qRes) === true) {
				return $qRes;
			} else {
				throw new \Exception("Failed to add queue: " . $id);
			}
			
		} else {
			throw new \Exception("Queue exists, cannot add: " . $id);
		}
	}
	protected function getQueue($id)
	{
		if ($this->queueExists($id, false) === true) {
			return msg_get_queue($id);
		} else {
			return null;
		}
	}
	protected function removeQueue($id)
	{
		if ($this->queueExists($id, false) === true) {
			$qRes		= msg_get_queue($id);
			$isValid	= msg_remove_queue($qRes);
			if ($isValid === false) {
				throw new \Exception("Failed to remove queue: " . $id);
			}
		}
		return $this;
	}
	protected function resetQueue($id)
	{
		$this->removeQueue($id);
		return $this->addQueue($id);
	}
}