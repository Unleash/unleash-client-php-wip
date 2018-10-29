<?php

namespace Unleash;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Unleash\Events\ErrorEvent;
use Unleash\Events\PersistedEvent;

class Storage extends EventDispatcher
{
    private $ready = false;
    private $data = [];
    private $path;

    public function __construct(string $backupPath, string $appName)
    {
        $this->path = $backupPath . '/unleash/repo/schema-v1-' . $this->safeAppName($appName) . '.json';
        $this->load();
    }

    public function safeAppName(string $appName)
    {
        return str_replace('/', '_', $appName);
    }

    public function reset(array $features, bool $doPersist = true)
    {
        $doEmitReady = $this->ready === false;
        $this->ready = true;
        $this->data = $features;
        if ($doEmitReady) {
            $this->dispatch('ready');
        }

        if ($doPersist) {
            $this->persist();
        }
    }

    public function get(string $key): ?FeatureInterface
    {
        return $this->data[$key];
    }

    public function persist()
    {
        $file = @fopen($this->path, "w");
        if ($file === false) {
            $this->dispatch('error', new ErrorEvent(error_get_last()));
            return;
        }

        fwrite($file, json_encode($this->data));
        fclose($file);

        $this->dispatch('persisted', new PersistedEvent());
    }

    public function load()
    {
        if ($this->ready) {
            return;
        }

        $data = @file_get_contents($this->path);
        if ($data === false) {
            $error = error_get_last();
            if (!strpos($error['message'], 'failed to open stream: No such file or directory') !== false) {
                $this->dispatch('error', new ErrorEvent($error));
            }

            return;
        }

        $this->reset(json_decode($data, true), false);
        //@todo: do something with json_last_error
    }
}