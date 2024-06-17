<?php

namespace Binance\Futures;

use Binance\Util\Strings;
use Binance\Exception\MissingArgumentException;

trait Stream
{
    /**
     * Create a ListenKey (USER_STREAM)
     *
     * POST /fapi/v1/listenKey
     *
     * Start a new user data stream.
     * The stream will close after 60 minutes unless a keepalive is sent. If the account has an active `listenKey`, that `listenKey` will be returned and its validity will be extended for 60 minutes.
     *
     * Weight: 1
     */
    public function newListenKey()
    {
        return $this->publicRequest('POST', '/fapi/v1/listenKey');
    }

    /**
     * Ping/Keep-alive a ListenKey (USER_STREAM)
     *
     * PUT /fapi/v1/listenKey
     *
     * Keepalive a user data stream to prevent a timeout. User data streams will close after 60 minutes. It's recommended to send a ping about every 30 minutes.
     *
     * Weight: 1
     *
     * @param string $listenKey
     * @throws MissingArgumentException
     */
    public function renewListenKey(string $listenKey)
    {
        if (Strings::isEmpty($listenKey)) {
            throw new MissingArgumentException('listenKey');
        }

        return $this->publicRequest(
            'PUT',
            '/fapi/v1/listenKey',
            [
                'listenKey' => $listenKey
            ]
        );
    }

    /**
     * Close a ListenKey (USER_STREAM)
     *
     * DELETE /fapi/v1/listenKey
     *
     * Close out a user data stream.
     *
     * Weight: 1
     *
     * @param string $listenKey
     */
    public function closeListenKey(string $listenKey)
    {
        if (Strings::isEmpty($listenKey)) {
            throw new MissingArgumentException('listenKey');
        }

        return $this->publicRequest(
            'DELETE',
            '/fapi/v1/listenKey',
            [
                'listenKey' => $listenKey
            ]
        );
    }
}
