<?php
//© 2019 Martin Peter Madsen
namespace MTM\Queues\Models\Message\SystemV;

class Queue extends Base
{
	protected $_id=null;
	protected $_guid=null;
	protected $_name=null;
	protected $_isInit=false;
	protected $_initTime=null;
	protected $_isTerm=false;
	protected $_keepAlive=true;
	protected $_perm="0666";
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
	public function setData($data, $type=1, $block=false, $throw=true)
	{
		//max msg size: ipcs -ql
		$rObj			= new \stdClass();
		$rObj->exeTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch();
		$rObj->runTime	= null;
		$rObj->status	= null;

		$isValid		= msg_send($this->getRes(), $type, $data, true, $block, $error);
		$rObj->runTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch() - $rObj->exeTime;
		if ($isValid === true) {
			$rObj->status	= "success";
		} else {
			if ($throw === true) {
				throw new \Exception("Failed to send set message in queue", $error);
			} else {
				$rObj->status	= "error: " . $error;
			}
		}
		return $rObj;
	}
	public function getData($type=null, $maxSize=null, $timeout=10000, $throw=true)
	{
		if ($type === null) {
			$type	= 0;
		}
		if ($maxSize === null) {
			$maxSize	= 16384;
		}

		$rObj			= new \stdClass();
		$rObj->success	= null;
		$rObj->type		= null;
		$rObj->data		= null;
		
		if ($timeout >= 0) {
			//non blocking
			$flags		= MSG_IPC_NOWAIT;
			$tTime		= \MTM\Utilities\Factories::getTime()->getMicroEpoch() + ($timeout / 1000);
			while(true) {
				$isValid	= msg_receive($this->getRes(), $type, $msgtype, $maxSize, $data, true, $flags, $error);
				if ($isValid === true) {
					break;
				} elseif (\MTM\Utilities\Factories::getTime()->getMicroEpoch() > $tTime) {
					break;
				} else {
					usleep(10000);
				}
			}
			
		} else {
			//negative timeout means blocking
			$flags		= 0;
			$isValid	= msg_receive($this->getRes(), $type, $msgtype, $maxSize, $data, true, $flags, $error);
		}
		
		$rObj->runTime	= \MTM\Utilities\Factories::getTime()->getMicroEpoch() - $rObj->exeTime;
		if ($isValid === true) {
			$rObj->success	= true;
			$rObj->type		= $msgtype;
			$rObj->data		= $data;
			return $rObj;
		} elseif ($timeout >= 0 && $error == MSG_ENOMSG) {
			//no messages in queue
			$rObj->status	= "Queue is empty";
		} else {
			$rObj->status	= "Error code: " . $error;
		}
		
		if ($throw === true) {
			throw new \Exception($rObj->status, $error);
		} else {
			return $rObj;
		}
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
				
			} else {
				throw new \Exception("Cannot initialize without an ID");
			}
		}
		return $this;
	}
	public function terminate()
	{
		if ($this->_isTerm === false) {
			$this->_isTerm	= true;

			if ($this->_isInit === true) {
				if ($this->getKeepAlive() === false) {
					msg_remove_queue($this->getRes());
				}
				$this->_queueRes	= null;
			}
			$this->getParent()->removeQueue($this);
		}
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
	public function setKeepAlive($bool)
	{
		//remove the resource on terminate if we are the last
		//connected
		$this->_keepAlive	= $bool;
		return $this;
	}
	public function getKeepAlive()
	{
		return $this->_keepAlive;
	}
	protected function getRes()
	{
		return $this->_queueRes;
	}
}