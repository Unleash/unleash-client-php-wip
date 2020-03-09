<?php

namespace Unleash\ClientExample;

use Symfony\Component\Console\Input\InputArgument;

class CacheRemoveCommand extends AbstractCacheCommand
{
    protected static $defaultName = 'cache:remove';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Gets an entry from the cache')
            ->addArgument('name', InputArgument::REQUIRED, 'name of the cache entry')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('');
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    )
    {
        self::$cache->clear();
    }
}