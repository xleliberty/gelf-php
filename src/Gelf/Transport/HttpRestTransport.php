<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Transport;

use Gelf\Encoder\EncoderInterface;
use Gelf\MessageInterface as Message;
use Gelf\Encoder\CompressedJsonEncoder as DefaultEncoder;
use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Url;

use RuntimeException;

/**
 * UdpTransport allows the transfer of GELF-messages to an compatible GELF-UDP-backend
 * as described in https://github.com/Graylog2/graylog2-docs/wiki/GELF
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class HttpRestTransport implements TransportInterface
{

    const DEFAULT_HOST     = "127.0.0.1";
    const DEFAULT_PORT     = 12201;
    const DEFAULT_ENDPOINT = "/gelf";
    const DEFAULT_SCHEME   = "http";

    const CONNECTION_TIMEOUT = 60;


    /**
     * @var EncoderInterface
     */
    protected $messageEncoder;

    /**
     * @var Guzzleclient
     */
    protected $restClient;

    /**
     * Class constructor
     *
     * @param string $host      when NULL or empty DEFAULT_HOST is used
     * @param int $port         when NULL or empty DEFAULT_PORT is used
     * @param int $chunkSize    defaults to CHUNK_SIZE_WAN, 0 disables chunks completely
     */
    public function __construct(
        $host     = self::DEFAULT_HOST,
        $port     = self::DEFAULT_PORT,
        $endpoint = self::DEFAULT_ENDPOINT,
        $scheme   = self::DEFAULT_SCHEME
    )
    {
        // allow NULL-like values for fallback on default
        $host     = $host ?: self::DEFAULT_HOST;
        $port     = $port ?: self::DEFAULT_PORT;
        $endpoint = $endpoint ?: self::DEFAULT_ENDPOINT;
        $scheme   = $scheme ?: self::DEFAULT_SCHEME;

        $url = Url::buildUrl(
            [
                'scheme' => $scheme,
                'host'   => $host,
                'port'   => $port,
                'path'   => $endpoint
            ]
        );

        $this->restClient = new GuzzleClient($url);
    }

    /**
     * Sets a message encoder
     *
     * @param EncoderInterface $encoder
     */
    public function setMessageEncoder(EncoderInterface $encoder)
    {
        $this->messageEncoder = $encoder;
    }

    /**
     * Returns the current message encoder
     *
     * @return EncoderInterface
     */
    public function getMessageEncoder()
    {
        if (!$this->messageEncoder) {
            $this->messageEncoder = new DefaultEncoder();
        }

        return $this->messageEncoder;
    }

    /**
     * Sends a Message over this transport
     *
     * @param Message $message
     *
     * @return int the number of UDP packets sent
     */
    public function send(Message $message)
    {
        $rawMessage = $this->getMessageEncoder()->encode($message);

        // send message in one packet
        $request = $this->restClient->post(null, null, $rawMessage);
        $request->send();

        return 1;
    }
}
