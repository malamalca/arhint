<?php
declare(strict_types=1);

namespace Documents\Test\TestCase;

use Documents\Lib\FursClient;
use RuntimeException;

/**
 * FursClient that never touches the network.
 */
class FakeFursClient extends FursClient
{
    /**
     * Queued responses; a response prefixed with "throw:" throws a RuntimeException with the rest as message.
     *
     * @var array<int, string>
     */
    public array $responses = [];

    public int $sent = 0;

    public ?string $lastRequest = null;

    public function sendInvoice(string $signedXml): string
    {
        $this->sent++;
        $this->lastRequest = $signedXml;

        $response = array_shift($this->responses) ?? '';
        if (str_starts_with($response, 'throw:')) {
            throw new RuntimeException(substr($response, 6));
        }

        return $response;
    }
}
