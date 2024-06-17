<?php
// This is a Futures REST API
// Futures functionality will be added in traits
// like it is done for Spot
namespace Binance;

class Futures extends APIClient
{
    use Futures\Stream;
    // You can add more traits related to Futures endpoints as needed.

    public function __construct(array $args = [])
    {
        $args['baseURL'] = $args['baseURL'] ?? 'https://fapi.binance.com';
        parent::__construct($args);
    }
}
