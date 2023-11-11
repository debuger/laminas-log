<?php

declare(strict_types=1);

namespace LaminasTest\Log\TestAsset;

class MockDbPlatform
{
    private $calls = [];

    public function __call($method, $params)
    {
        $this->calls[$method][] = $params;
    }
}
