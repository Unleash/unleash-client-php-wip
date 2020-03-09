<?php

namespace Unleash\ClientExample;

class CacheClearCommand extends AbstractCacheCommand
{
    protected static $defaultName = 'cache:clear';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Clears the cache')
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
        $output->writeln('Cache cleared');
    }
}