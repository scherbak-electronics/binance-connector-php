<?php
// This is Futures WebSocket API
namespace Binance\Websocket;

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


    public function startUserDataStream(): void
    {
        $params = [
            'apiKey' => $this->apiKey,
        ];

        $request = [
            'id' => uniqid(),
            'method' => 'userDataStream.start',
            'params' => $params
        ];

        if ($this->wsConnection) {
            $this->wsConnection->send(json_encode($request));
        } else {
            $this->logger->warning("WebSocket connection is not established. Request cannot be sent.");
        }
    }
}
