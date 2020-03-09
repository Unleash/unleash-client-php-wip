<?php
declare(strict_types=1);

if (!function_exists('toNewFormat')) {
    /**
     * @param array $data
     * @return Unleash\Feature[]
     */
    function pickData(array $data)
    {
        $features = [];
        foreach ($data['features'] as $row) {
            $feature = new Unleash\Feature();
            $feature->name = $row['name'];
            $feature->enabled = $row['enabled'];
            $feature->strategies = [new \Unleash\Strategy\StrategyTransportInterface($row['strategy'], $row['parameters'])];
        }

        return [
            'version'  => 1,
            'features' => $features,
        ];
    }
}
