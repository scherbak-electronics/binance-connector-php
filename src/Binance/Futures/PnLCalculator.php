<?php
namespace Binance\Futures;
// This is the PnLCalculator class
class PnLCalculator
{
    private $positionSize;
    private $entryPrice;
    private $markPrice;
    private $leverage;
    private $side;

    public function __construct($positionSize, $entryPrice, $markPrice, $leverage, $side)
    {
        $this->positionSize = $positionSize;
        $this->entryPrice = $entryPrice;
        $this->markPrice = $markPrice;
        $this->leverage = $leverage;
        $this->side = $side;
    }

    public function calculateUnrealizedPnL()
    {
        if ($this->side == 'BUY') {
            $unrealizedPnL = $this->positionSize * ($this->markPrice - $this->entryPrice);
        } else { // 'SELL'
            $unrealizedPnL = $this->positionSize * ($this->entryPrice - $this->markPrice);
        }
        return $unrealizedPnL;
    }

    public function calculateROI()
    {
        //$IMR = 1 / $this->leverage;
        // Calculate the initial investment value
        $initialValue = abs($this->positionSize) * $this->entryPrice;
        //$entryMargin = abs($this->positionSize) * $this->entryPrice * $IMR;
        $unrealizedPnL = $this->calculateUnrealizedPnL();
        
        // Calculate current value or unrealized profit/loss
        $currentValue = $initialValue + $unrealizedPnL;
        //$ROI = ($unrealizedPnL / $entryMargin) * 100;
        // Calculate ROI percentage
        if ($initialValue != 0) {
            $ROI = (($currentValue - $initialValue) / $initialValue) * 100;
        } else {
            $ROI = 0; // Handle edge case where initialValue is zero
        }
        return $ROI;
    }
}
