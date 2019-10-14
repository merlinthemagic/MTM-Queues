# MTM-Queues

## System V:


### Get a queue:

```
$name		= "myQueueName";
$perm		= "0600";
$msgFact	= \MTM\Queues\Factories::getMessages()->getSystemFive();
$queueObj	= $msgFact->getQueue($name, $perm);
```

### Add a message to the queue

```
$data		= "some data";
$type		= 1; //1 is default
$queueObj->setData($data, $type);

```

### get a message to the queue

```
$type		= 0; //0 is return any message, if you are looking for a specific message type, change this
$msgObj	= $queueObj->getData($type); //by default not blocking, will return object with type === null on empty

```
