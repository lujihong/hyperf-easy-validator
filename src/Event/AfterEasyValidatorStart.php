<?php

declare(strict_types=1);

namespace Hyperf\EasyValidator\Event;

use Hyperf\HttpServer\Router\RouteCollector;
use Hyperf\RpcServer\Router\RouteCollector as RpcRouteCollector;

class AfterEasyValidatorStart
{
    public array $serverConfig;

    /**
     * @var RouteCollector|RpcRouteCollector
     */
    public mixed $router;

    public function __construct(array $serverConfig, $router)
    {
        $this->router = $router;
        $this->serverConfig = $serverConfig;
    }
}
