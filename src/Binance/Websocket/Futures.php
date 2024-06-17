<?php
// This is Futures WebSocket API
namespace Binance\Websocket;

use Binance\Exception\InvalidArgumentException;

class Futures extends Spot
{
    public function __construct(array $args = [])
    {
        $args['baseURL'] = $args['baseURL'] ?? 'wss://fstream.binance.com';
        parent::__construct($args);
    }

    public function requestPositionInfo($symbol): void
    {
        $params = ['symbol' => $symbol];
        $this->sendSignedRequest('account.position', $params);
    }


    /**
     * @throws InvalidArgumentException
     */
    public function startUserDataStream($callback): void
    {
        $params = [
            'apiKey' => $this->apiKey,
        ];

        $request = [
            'id' => uniqid(),
            'method' => 'userDataStream.start',
            'params' => $params
        ];

        $this->handleCallBack($this->baseURL, function ($conn, $msg) use ($callback, $request) {
            $conn->send(json_encode($request));
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
        });
    }
}
