<?php

namespace Unleash\ClientExample;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

class AbstractCacheCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var CacheInterface
     */
    static protected $cache;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->setUpDemoCache();
    }

    public function setUpDemoCache()
    {
        /** Ensure, we only have one global cache instance in all commands */
        if (self::$cache === null) {
            self::$cache = new Psr16Cache(
                new FilesystemAdapter('local')
            );
        }
    }
}