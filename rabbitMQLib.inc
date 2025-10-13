<?php

require_once('get_host_info.inc');

class rabbitMQServer
{
	private $machine = "";
	public  $BROKER_HOST;
	private $BROKER_PORT;
	private $USER;
	private $PASSWORD;
	private $VHOST;
	private $exchange;
	private $queue;
	private $routing_key = ''; //routing key will be handled later - removed "*"
	private $exchange_type = "topic";
	private $auto_delete = false;

	function __construct($machine, $server = "rabbitMQ")
	{
		$this->machine = getHostInfo(array($machine));
		$this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"];
		$this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];
		$this->USER     = $this->machine[$server]["USER"];
		$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		$this->VHOST = $this->machine[$server]["VHOST"];
		if (isset( $this->machine[$server]["EXCHANGE_TYPE"]))
		{
			$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
		}
		if (isset( $this->machine[$server]["AUTO_DELETE"]))
		{
			$this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
		}
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
		$this->routing_key = $this->queue; //Matches routing key to queue to ensure consistency in message routing
	}

	//removed process_message function and replaced with polling loop in process_requests	

	function process_requests($callback)
	{
		try
		{
			$this->callback = $callback;
			$params = array();
			$params['host'] = $this->BROKER_HOST;
			$params['port'] = $this->BROKER_PORT;
			$params['login'] = $this->USER;
			$params['password'] = $this->PASSWORD;
			$params['vhost'] = $this->VHOST;

			$conn = new AMQPConnection($params);
			$conn->connect();

			$channel = new AMQPChannel($conn);
			$exchange = new AMQPExchange($channel);
			$exchange->setName($this->exchange);
			$exchange->setType($this->exchange_type);
			$exchange->setFlags(AMQP_DURABLE); //Set exchange to durable
			$exchange->declareExchange();

			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->setFlags(AMQP_DURABLE); //Set queue to durable
			$this->conn_queue->declareQueue();
			$this->conn_queue->bind($exchange->getName(), $this->routing_key); //Removed hardcoded bindings, replaced with dynamic routing key

			echo " [*] Listening for messages in queue: {$this->queue}\n"; //Status message to demonstrate queue is listening 

			//Replaced old consume() function with continuous polling loop
			while (true) {
				$envelope = $this->conn_queue->get(); //manual ack
				if ($envelope) {
					$body = $envelope->getBody();
					$payload = json_decode($body, true); //Decodes message into array once received

					//Checks if message is handled by a callback function
					if (isset($this->callback)) {
						$response = call_user_func($this->callback, $payload);
						//Gets queue name to know where to send response back
						$replyTo = $envelope->getReplyTo();
						$correlationID = $envelope->getCorrelationId();
						
						//If a reply queue is specified, it sends a response back to the requester
						if(!empty($replyTo)) {
							$reply_exchange = new AMQPExchange($channel);
							$reply_exchange->setName('');
							$reply_exchange->publish(json_encode($response), $replyTo, AMQP_NOPARAM, ['correlation_id' => $correlationID]); //Uses ID to match response with request
						}
					}
					$this->conn_queue->ack($envelope->getDeliveryTag()); //Acks messages after processing
					echo " Message has been processed\n"; //Status update
				} else {
					usleep(100000); //Avoids CPU spike
				} 
			}
			$conn->disconnect(); //Closes connection when loop is ended
		}
		catch (Exception $e)
		{
			trigger_error("Failed to start request processor: " . $e, E_USER_ERROR); 
		}
	}
}

class rabbitMQClient
{

	private $machine = "";
	public  $BROKER_HOST;
	private $BROKER_PORT;
	private $USER;
	private $PASSWORD;
	private $VHOST;
	private $exchange;
	private $queue;
	private $routing_key = ''; //Routing key specified earlier - removed '*'
	private $response_queue = array();
	private $exchange_type = "topic";

	function __construct($machine, $server = "rabbitMQ")
	{
		$this->machine = getHostInfo(array($machine));
		$this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"];
		$this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];
		$this->USER     = $this->machine[$server]["USER"];
		$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		$this->VHOST = $this->machine[$server]["VHOST"];
		if (isset( $this->machine[$server]["EXCHANGE_TYPE"]))
		{
			$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
		}
		if (isset( $this->machine[$server]["AUTO_DELETE"]))
		{
			$this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
		}
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
		$this->routing_key = $this->queue; //Matches routing key to queue name
	}

	function process_response($response)
	{
		$uid = $response->getCorrelationId();
		if (!isset($this->response_queue[$uid]))
		{
		  echo  "unknown uid\n";
		  return true;
		}
    $this->conn_queue->ack($response->getDeliveryTag());
		$body = $response->getBody();
		$payload = json_decode($body, true);
		if (!(isset($payload)))
		{
			$payload = "[empty response]";
		}
		$this->response_queue[$uid] = $payload;
		return false;
	}

	function send_request($message)
	{
		$uid = uniqid();
		$json_message = json_encode($message)
;
		try
		{
			$params = array();
			$params['host'] = $this->BROKER_HOST;
			$params['port'] = $this->BROKER_PORT;
			$params['login'] = $this->USER;
			$params['password'] = $this->PASSWORD;
			$params['vhost'] = $this->VHOST;

			$conn = new AMQPConnection($params);
			$conn->connect();

			$channel = new AMQPChannel($conn);
			$exchange = new AMQPExchange($channel);
			$exchange->setName($this->exchange);
			$exchange->setType($this->exchange_type);
			$exchange->setFlags(AMQP_DURABLE); //Setting exchange to durable, messages stay after server restarts

			$callback_queue = new AMQPQueue($channel);
			$callback_queue->setName($this->queue . "_response");
			$callback_queue->declareQueue();
			$callback_queue->bind($exchange->getName(), $this->routing_key . ".response");

			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(), $this->routing_key);
			
			$exchange->publish($json_message,$this->routing_key,AMQP_NOPARAM,array('reply_to'=>$callback_queue->getName(),'correlation_id'=>$uid));
			$this->response_queue[$uid] = "waiting";
			$callback_queue->consume(array($this, 'process_response'));

			$response = $this->response_queue[$uid];
			unset($this->response_queue[$uid]);
			return $response;
		}
		catch(Exception $e)
		{
			die("failed to send message to exchange: " . $e->getMessage() . "\n");
		}
	}

	function publish($message)
	{
		$json_message = json_encode($message);
		try
		{
			$params = array();
			$params['host'] = $this->BROKER_HOST;
			$params['port'] = $this->BROKER_PORT;
			$params['login'] = $this->USER;
			$params['password'] = $this->PASSWORD;
			$params['vhost'] = $this->VHOST;
			
			$conn = new AMQPConnection($params);
			$conn->connect();
			
			$channel = new AMQPChannel($conn);
			$exchange = new AMQPExchange($channel);
			$exchange->setName($this->exchange);
			$exchange->setType($this->exchange_type);
			$exchange->setFlags(AMQP_DURABLE); //Setting exchange to durable

			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(), $this->routing_key);
			
			return $exchange->publish($json_message, $this->routing_key);
		}
		catch(Exception $e)
		{
			die("failed to send message to exchange: " . $e->getMessage() . "\n");
		}
	}
}
?>
