<?php

namespace Binance\Websocket;

use Binance\Websocket;

class Futures extends Spot
{
    public function __construct(array $args = [])
    {
        $args['baseURL'] = $args['baseURL'] ?? 'wss://fstream.binance.com';
        parent::__construct($args);
    }
}
