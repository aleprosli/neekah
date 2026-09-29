<?php

namespace App\Support;

use Closure;
use InvalidArgumentException;

/**
 * Neekah fetches a calendar address a vendor typed in, from its own server.
 * Nothing inside the network may be reached that way: the address must be
 * https and its host must resolve only to public addresses. The address the
 * check approved is the one connected to (pinned), so a DNS answer cannot
 * change between the check and the fetch.
 */
class IcalUrlGuard
{
    /** @var Closure(string): list<string> */
    private Closure $resolve;

    /**
     * @param  (Closure(string): list<string>)|null  $resolve  host → IPv4 addresses
     */
    public function __construct(?Closure $resolve = null)
    {
        $this->resolve = $resolve ?? fn (string $host): array => gethostbynamel($host) ?: [];
    }

    /**
     * The host and the public address to connect to.
     *
     * @return array{host: string, ip: string}
     *
     * @throws InvalidArgumentException
     */
    public function check(string $url): array
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https' || blank($parts['host'] ?? null) || isset($parts['user']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw new InvalidArgumentException('Only https addresses on the standard port are allowed.');
        }

        $host = strtolower($parts['host']);
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->resolve)($host);

        if ($addresses === []) {
            throw new InvalidArgumentException('The host does not resolve.');
        }

        foreach ($addresses as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('The host resolves to a private address.');
            }
        }

        return ['host' => $host, 'ip' => $addresses[0]];
    }
}
