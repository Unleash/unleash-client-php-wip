<?php

namespace Unleash\ClientExample;


use Symfony\Component\Console\Input\InputArgument;

class CheckFeatureStateCommand extends AbstractCacheCommand
{
    protected static $defaultName = 'check:state';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Clears the cache')
            // the full command description shown when running the command with
            // the "--help" option
            ->addArgument('appName', InputArgument::REQUIRED, 'App Name for Client')
            ->addArgument('url', InputArgument::REQUIRED, 'App Name for Client')
            ->addArgument('instance', InputArgument::OPTIONAL, 'App Name for Client', null)
            ->addArgument('feature', InputArgument::OPTIONAL, 'App Name for Client', null)
            ->setHelp('');
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    )
    {
        ini_set('display_errors', 1);

        $unleashClient = new \Unleash\Unleash;
        $unleashClient->initialize(
            $input->getArgument('appName'),
            $input->getArgument('url'),
            $input->getArgument('instance'),
            null
        );

        // to retrieve the current state of the feature flags
        $unleashClient->fetch();

        // check if a feature is enabled
        if ($unleashClient->isEnabled($unleashClient->isEnabled($input->getArgument('feature')))) {
            echo '✅ feature is enabled';
        } else {
            echo '❌ feature is not enabled';
        }
    }
}
