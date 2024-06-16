<?php

namespace Binance;

use Psr\Log\NullLogger;
use Ratchet\RFC6455\Messaging\Frame;
use Binance\Exception\InvalidArgumentException;
use function Ratchet\Client\connect;

abstract class Websocket
{
    protected string $baseURL;
    protected $logger;
    protected $wsConnector;
    public $wsConnection;
    protected string $apiKey;
    protected string $secretKey;

    public function __construct($args = array())
    {
        $this->logger  = $args['logger'] ?? new NullLogger();
        $this->baseURL = $args['baseURL'];
        $this->wsConnector = $args['wsConnector'] ?? null;
        $this->apiKey  = $args['apiKey'] ?? null;
        $this->secretKey = $args['secretKey'] ?? null;
    }

    public function ping(): void
    {
        if ($this->wsConnection) {
            $this->wsConnection->send(new Frame('', true, Frame::OP_PING));
        } else {
            $this->logger->warning("ping can't be sent before ws connection established.");
        }
    }

    /**
     * $callback can be used:
     *   - if it's function, it will be used to process the `message` event.
     *   - if it's an array, it's required to specific the event type for each function. e.g. ['message' => function(){}, 'ping' => function(){}]
     * @throws InvalidArgumentException
     */
    protected function handleCallBack($url, $callback): void
    {
        if (is_array($callback)) {
            foreach ($callback as $event => $func) {
                if (gettype($event) == "integer") {
                    throw new InvalidArgumentException("callback", json_encode($callback), "Array has event type as key, e.g. ['message' => function() {}]", "Please find example at https://github.com/binance/binance-connector-php");
                }
            }
        }
        $this->createWSConnection($url)->then(function ($conn) use ($callback) {
            $this->wsConnection = $conn;
            if (is_callable($callback)) {
                $conn->on('message', function ($msg) use ($conn, $callback) {
                    $callback($conn, $msg);
                });
            }

            if (is_array($callback)) {
                foreach ($callback as $event => $func) {
                    $event = strtolower(strval($event));
                    if (in_array($event, ['message', 'ping', 'pong', 'close'])) {
                        $conn->on($event, function ($msg) use ($conn, $func) {
                            call_user_func($func, $conn, $msg);
                        });
                    }
                }
            }
        }, function ($e) {
            $this->logger->error("Could not connect: {$e->getMessage()}".PHP_EOL);
        });
    }


    private function createWSConnection($url)
    {
        if ($this->wsConnector) {
            return call_user_func($this->wsConnector, $url);
        } else {
            return connect($url);
        }
    }

    protected function sendSignedRequest($method, $params): void
    {
        $timestamp = round(microtime(true) * 1000);
        $params['timestamp'] = $timestamp;
        $query = http_build_query($params);
        $signature = hash_hmac('sha256', $query, $this->secretKey);
        $params['signature'] = $signature;

        $request = [
            'id' => uniqid(),
            'method' => $method,
            'params' => array_merge($params, ['apiKey' => $this->apiKey])
        ];

        if ($this->wsConnection) {
            $this->wsConnection->send(json_encode($request));
        } else {
            $this->logger->warning("WebSocket connection is not established. Request cannot be sent.");
        }
    }
}
