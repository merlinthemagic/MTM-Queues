<?php
//© 2019 Martin Peter Madsen
namespace MTM\Queues\Models\Message\SystemV;

class Queue extends Base
{
	protected $_id=null;
	protected $_guid=null;
	protected $_name=null;
	protected $_isInit=false;
	protected $_isTerm=false;
	protected $_initTime=null;
	protected $_perm="0666";
	protected $_maxSize=null;
	protected $_queueRes=null;
	
	public function __construct($id)
	{
		$this->_id		= $id;
		$this->_guid	= \MTM\Utilities\Factories::getGuids()->getV4()->get(false);
	}
	public function __destruct()
	{
		$this->terminate();
	}
	public function isInit()
	{
		return $this->_isInit;
	}
	public function isTerm()
	{
		return $this->_isTerm;
	}
	public function setData($data, $type=null, $block=false, $throw=true)
	{
		//max msg size: ipcs -ql
		if ($type === null) {
			$type	= 1;
		}
		
		$isValid	= msg_send($this->getRes(), $type, $data, true, $block, $errNbr);
		if ($isValid === false) {

			$qMax	= $this->getParent()->getMaxQueueSize();
			$mMax	= $this->getParent()->getMaxMessageSize();
			$size		= strlen(serialize($data));
			if ($size > $qMax) {
				throw new \Exception("Failed to set message in queue, max queue size: " . $qMax . " exceeded: " . $size, $errNbr);
			} elseif ($size > $mMax) {
				throw new \Exception("Failed to set message in queue, max message size: " . $mMax . " exceeded: " . $size, $errNbr);
			} else {
				throw new \Exception("Failed to set message in queue", $errNbr);
			}
		}
		return $this;
	}
	public function getData($type=null, $maxSize=null, $timeout=10000, $throw=true)
	{
		if ($type === null) {
			$type	= 0;
		}
		if ($maxSize === null) {
			//cat /proc/sys/kernel/msgmnb
			$maxSize	= $this->_maxSize;
		}
		if ($timeout < 0) {
			
			$flags		= 0;//negative timeout means blocking
			$isValid	= msg_receive($this->getRes(), $type, $msgtype, $maxSize, $data, true, $flags, $errorNbr);
			if ($isValid === true) {
				return (object) array("type" => $msgtype, "msg" => $data);
			} else {
				throw new \Exception("Failed to get message from queue", $errorNbr);
			}
			
		} else {
			
			$flags		= MSG_IPC_NOWAIT; //0+ means non blocking
			$tTime		= \MTM\Utilities\Factories::getTime()->getMicroEpoch() + ($timeout / 1000);
			while(true) {
				$isValid	= msg_receive($this->getRes(), $type, $msgtype, $maxSize, $data, true, $flags, $errorNbr);
				if ($isValid === true) {
					return (object) array("type" => $msgtype, "msg" => $data);
				} elseif ($errorNbr != MSG_ENOMSG) {
					throw new \Exception("Error receiving message", $errorNbr);//only no message errors are benign
				} elseif (\MTM\Utilities\Factories::getTime()->getMicroEpoch() > $tTime) {
					if ($throw === true) {
						throw new \Exception("Timeout getting message from queue", $errorNbr);
					} else {
						return (object) array("type" => null, "msg" => null);
					}
				} else {
					usleep(10000);
				}
			}
		}
	}
	public function clear()
	{
		//clear all messages from queue
		while(true) {
			$msgObj	= $this->getData(null, null, 0, false);
			if ($msgObj->type === null) {
				break;
			}
		}
		return $this;
	}
	public function initialize()
	{
		if ($this->_isInit === false) {
			if ($this->getId() !== null) {
				
				$queueRes	= msg_get_queue($this->getId(), intval($this->getPermission(), 8));
				if (is_resource($queueRes) === true) {
					$this->_queueRes		= $queueRes;
				} else {
					throw new \Exception("Failed to get message queue");
				}
				$this->_initTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
				$this->_isInit		= true;
				//max receive size
				$this->_maxSize		= $this->getMetaData()->size;
				
			} else {
				throw new \Exception("Cannot initialize without an ID");
			}
		}
		return $this;
	}
	public function terminate()
	{
		if ($this->isTerm() === false) {
			$this->_isTerm		= true;
			$this->_queueRes	= null;
			$this->getParent()->removeQueue($this);
		}
		return $this;
	}
	public function delete()
	{
		$this->getParent()->deleteQueue($this);
		return $this;
	}
	public function getGuid()
	{
		return $this->_guid;
	}
	public function getId()
	{
		return $this->_id;
	}
	public function setName($name)
	{
		$this->_name	= $name;
		return $this;
	}
	public function getName()
	{
		return $this->_name;
	}
	public function setPermission($str)
	{
		$this->_perm	= $str;
		return $this;
	}
	public function getPermission()
	{
		return $this->_perm;
	}
	public function getMetaData()
	{
		if ($this->_isInit === true) {
			$stats			= msg_stat_queue($this->getRes());
			$mode			= substr(sprintf("%o", $stats["msg_perm.mode"]), -4);
			
			$rObj			= new \stdClass();
			$rObj->perm		= str_repeat("0", 4 - strlen($mode)) . $mode;
			$rObj->uId		= $stats["msg_perm.uid"];
			$rObj->gId		= $stats["msg_perm.gid"];
			$rObj->size		= $stats["msg_qbytes"];
			$rObj->msgCount	= $stats["msg_qnum"];
			
			return $rObj;

		} else {
			throw new \Exception("Queue is not yet initialized");
		}
	}
	protected function getRes()
	{
		return $this->_queueRes;
	}
}