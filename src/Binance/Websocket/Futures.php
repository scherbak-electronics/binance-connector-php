<?php
// This is Futures WebSocket API
namespace Binance\Websocket;

use Binance\Exception\InvalidArgumentException;
use Binance\Futures\PnLCalculator;

class Futures extends Spot
{
    private $positionData = null;
    private $positionActive = false;

    public function __construct(array $args = [])
    {
        $args['baseURL'] = $args['baseURL'] ?? 'wss://fstream.binance.com';
        parent::__construct($args);
    }

    public function position($listenKey, $symbol, $callbacks)
    {
        $callback = $callbacks;
        if (is_array($callbacks) && !empty($callbacks['message'])) {
            $callback = $callbacks['message'];
        }
        // Subscribe to user data events
        $this->userData($listenKey, function ($conn, $msg) use ($symbol, $callback) {
            $event = json_decode($msg, true);
            if (isset($event['e']) && $event['e'] == 'ORDER_TRADE_UPDATE') {
                $order = $event['o'];
                if ($order['s'] === $symbol && $order['X'] === 'FILLED') {
                    // Check if it's an open or close action
                    $realizedProfit = (float)$order['rp'];
                    if ($realizedProfit === (float)0) {
                        // Position potentially opened or increased
                        $this->positionData = [
                            'symbol' => $symbol,
                            'positionAmt' => $order['q'],
                            'entryPrice' => $order['ap'],
                            'leverage' => $order['l'] ?? 1,
                            'side' => $order['S']
                        ];
                        $this->positionActive = true;
                        $callback($conn, json_encode([
                            'e' => 'open_position',
                            'position' => $this->positionData,
                            'original_event' => $event
                        ]));
                    } elseif ($realizedProfit !== (float)0) {
                        $this->positionActive = false;
                        $callback($conn, json_encode([
                            'e' => 'close_position',
                            'original_event' => $event,
                            'rp' => $realizedProfit
                        ]));
                    }
                }
            } elseif (isset($event['e']) && $event['e'] == 'ACCOUNT_UPDATE') {
                $accountUpdate = $event['a'];
                foreach ($accountUpdate['P'] as $position) {
                    if ($position['s'] === $symbol) {
                        if ($position['pa'] == 0) {
                            // Position closed
                            $this->positionData = null;
                            $this->positionActive = false;
                            $callback($conn, json_encode([
                                'e' => 'close_position',
                                'original_event' => $event
                            ]));
                            return;
                        }
                    }
                }
            }
        });

        // Subscribe to ticker events
        $this->ticker(function ($conn, $msg) use ($callback) {
            $event = json_decode($msg, true);
            if ($this->positionActive && $this->positionData) {
                $markPrice = $event['c']; // The current mark price
                $positionSize = $this->positionData['positionAmt'];
                $entryPrice = $this->positionData['entryPrice'];
                $leverage = $this->positionData['leverage'];
                $side = $this->positionData['side'];
                $calculator = new PnLCalculator($positionSize, $entryPrice, $markPrice, $leverage, $side);
                $unrealizedPnL = $calculator->calculateUnrealizedPnL();
                $ROI = $calculator->calculateROI();
                $dataJson = json_encode([
                    'e' => 'opened_position_update',
                    'symbol' => $this->positionData['symbol'],
                    'entryPrice' => $this->positionData['entryPrice'],
                    'price' => $markPrice,
                    'qty' => $positionSize,
                    'pnl' => $unrealizedPnL,
                    'roi' => $ROI
                ]);
                $callback($conn, $dataJson);
            }
        }, $symbol);
    }
}
