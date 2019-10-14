<?php
//© 2019 Martin Peter Madsen
namespace MTM\Queues\Models\Message\SystemV;

class API
{
	protected $_queueObjs=array();
	protected $_keepAlive=true;
	
	public function getQueue($name=null, $perm=null)
	{
		if ($name === null) {
			$name	= \MTM\Utilities\Factories::getGuids()->getV4()->get(false);
		} else {
			$name	= trim($name);
		}
		
		$queueObj	= $this->getQueueByName($name, false);
		if ($queueObj === null) {
			
			$segId		= \MTM\Utilities\Factories::getStrings()->getHashing()->getAsInteger($name, 4294967295);
			$queueObj		= new \MTM\Queues\Models\Message\SystemV\Queue($segId);
			$queueObj->setParent($this)->setName($name)->setKeepAlive($this->getDefaultKeepAlive());

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
	public function setDefaultKeepAlive($bool)
	{
		//should shares delete once terminated if there are no other connections
		$this->_keepAlive	= $bool;
		return $this;
	}
	public function getDefaultKeepAlive()
	{
		return $this->_keepAlive;
	}
}