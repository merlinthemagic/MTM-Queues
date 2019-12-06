<?php
//© 2019 Martin Peter Madsen
namespace MTM\Queues\Models\Message\SystemV;

class API
{
	protected $_shellObj=null;
	protected $_queueObjs=array();
	
	public function getQueue($name=null, $perm=null)
	{
		if ($name === null) {
			$name	= \MTM\Utilities\Factories::getGuids()->getV4()->get(false);
		} else {
			$name	= trim($name);
		}
		
		$queueObj	= $this->getQueueByName($name, false);
		if ($queueObj === null) {
			
			$segId			= $this->getSegmentIdFromName($name);
			$queueObj		= new \MTM\Queues\Models\Message\SystemV\Queue($segId);
			$queueObj->setParent($this)->setName($name);

			if ($perm !== null) {
				$perm	= str_repeat("0", 4 - strlen($perm)) . $perm;
				$queueObj->setPermission($perm);
			}
			
			$exist		= msg_queue_exists($segId);
			$queueObj->initialize();
			if ($exist === true) {
				$meta	= $queueObj->getMetaData();
				if ($perm === null) {
					$queueObj->setPermission($meta->perm); //user did not specify, so we correct
				} elseif ($meta->perm != $perm) {
					$queueObj->terminate();
					throw new \Exception("Queue exists with permissions: " . $meta->perm . ", requested permissions: " . $perm);
				}
			}
			$hash						= hash("sha256", $name);
			$this->_queueObjs[$hash]	= $queueObj;
		} elseif ($perm !== null && $queueObj->getPermission() != $perm) {
			throw new \Exception("Queue exists with permissions: " . $queueObj->getPermission() . ", requested permissions: " . $perm);
		}
		return $queueObj;
	}
	public function getQueues()
	{
		return array_values($this->_queueObjs);
	}
	public function getMaxQueueSize()
	{
		//increse the queue overall size:
		//echo 131072 > /proc/sys/kernel/msgmnb
		//there is also a bunch of settings in "/proc/sys/fs/mqueue" but they do not seem to have any effect
		$strCmd		= "cat /proc/sys/kernel/msgmnb";
		$maxSize	= trim($this->getShell()->write($strCmd)->read()->data);
		if (is_numeric($maxSize) === true) {
			return intval($maxSize);
		} else {
			throw new \Exception("Failed to get max queue size");
		}
	}
	public function getMaxMessageSize()
	{
		//default max message size on Linux is 8192bytes
		//increse the queue max message size:
		//echo 131072 > /proc/sys/kernel/msgmax
		//there is also a bunch of settings in "/proc/sys/fs/mqueue" but they do not seem to have any effect
		$strCmd		= "cat /proc/sys/kernel/msgmax";
		$maxSize	= trim($this->getShell()->write($strCmd)->read()->data);
		if (is_numeric($maxSize) === true) {
			return intval($maxSize);
		} else {
			throw new \Exception("Failed to get max message size");
		}
	}
	public function getQueueExistByName($name)
	{
		$segId		= $this->getSegmentIdFromName($name);
		return msg_queue_exists($segId);
	}
	public function getQueueByName($name, $throw=false)
	{
		$hash	= hash("sha256", $name);
		if (array_key_exists($hash, $this->_queueObjs) === true) {
			return $this->_queueObjs[$hash];
		} elseif ($throw === true) {
			throw new \Exception("No queue with name: " . $name);
		} else {
			return null;
		}
	}
	public function removeQueue($queueObj)
	{
		$hash	= hash("sha256", $queueObj->getName());
		if (array_key_exists($hash, $this->_queueObjs) === true) {
			unset($this->_queueObjs[$hash]);
			$queueObj->terminate();
		}
		return $this;
	}
	public function deleteQueue($queueObj)
	{
		$segId	= $this->getSegmentIdFromName($queueObj->getName());
		$exist	= msg_queue_exists($segId);
		if ($exist === true) {
			$qRes		= msg_get_queue($segId);
			$isValid	= msg_remove_queue($qRes);
			if ($isValid === false) {
				throw new \Exception("Failed to delete queue: " . $queueObj->getName());
			}
		}
		$this->removeQueue($queueObj);
		return $this;
	}
	protected function getSegmentIdFromName($name)
	{
		//there seems to be a 32bit limit on the address space, if we do not limit we will not be able to find the share
		//attached count, because the max id can be 64bit/2
		return \MTM\Utilities\Factories::getStrings()->getHashing()->getAsInteger($name, 4294967295);
	}
	protected function getShell()
	{
		//cache in class to ensure its torn down after the API
		if ($this->_shellObj === null) {
			$this->_shellObj	= \MTM\Utilities\Factories::getSoftware()->getPhpTool()->getShell();
		}
		return $this->_shellObj;
	}
}