<?php
/**
 * Created by kay.
 */

namespace Unleash\ClientExample;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unleash\Context;

class SelfTestCommand extends AbstractCacheCommand
{
    protected static $defaultName = 'test:self';

    protected $indexUri = 'https://raw.githubusercontent.com/Unleash/client-specification/master/specifications/index.json';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('tests against the specification')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('');
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    )
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        $uris = $this->getUrisFromIndex();

        $output->writeln('Found endpoints:');
        foreach($uris as $uri) {
            $output->writeln(' * ' . $uri);
        }

        foreach ($uris as $uri) {
            $this->runChecksAgainstEndpoint($uri, $output);
        }

    }

    protected function getUrisFromIndex()
    {
        $absoluteUris = [];
        $baseUri = dirname($this->indexUri);
        $relativeUris = json_decode(file_get_contents($this->indexUri), true);


        foreach ($relativeUris as $relativeUri) {
            $absoluteUris[] = $baseUri . '/' . $relativeUri . '?';
        }

        return $absoluteUris;
    }

    protected function runChecksAgainstEndpoint(string $uri, OutputInterface $output)
    {
        // client
        $unleashClient = new \Unleash\Unleash;
        $unleashClient->initialize(
            'self-test',
            $uri,
            'self-test-cli',
            null
        );
        $unleashClient->fetch();

        // fetch testdata
        $testData = json_decode(file_get_contents($uri), true);

        // test

        $output->writeln($testData['name']);

        foreach ($testData['tests'] as $test) {
            $output->writeln('  ' . $test['description']);
            $context = new Context($test['context']);
            $response = $unleashClient->isEnabled($test['toggleName'], $context);
            if ($response === $test['expectedResult']) {
                $output->writeln('    <info>✅ passed</info>');
            } else {
                $output->writeln('    <error>❌ failed</error>');
                $output->writeln('    expected ' . $test['expectedResult'] . ' got ' . $response);
            }
        }
    }
}
