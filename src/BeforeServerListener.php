<?php

declare(strict_types=1);

namespace Hyperf\EasyValidator;

use Closure;
use Hyperf\Contract\ConfigInterface;
use Hyperf\EasyValidator\Event\AfterEasyValidatorStart;
use Hyperf\EasyValidator\Router\TcpRouter;
use Hyperf\EasyValidator\Scan\ScanAnnotation;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeServerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

class BeforeServerListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BeforeServerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof BeforeServerStart) {
            $serverName = $event->serverName;
        } else {
            /** @var MainCoroutineServerStart $event */
            $serverName = $event->name;
        }
        $container = ApplicationContext::getContainer();
        $config = $container->get(ConfigInterface::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $scanAnnotation = $container->get(ScanAnnotation::class);
        $serverConfig = collect($config->get('server.servers'))->where('name', $serverName)->first();

        //防止非http重复扫描注解
        if ($serverConfig['type'] !== \Hyperf\Server\Server::SERVER_HTTP) {
            return;
        }

        if (isset($serverConfig['callbacks']['receive'][0]) && str_contains($serverConfig['callbacks']['receive'][0], 'TcpServer')) {
            $tcpRouter = $container->get(TcpRouter::class);
            $router = $tcpRouter->getRouter($serverName);
        } else {
            $router = $container->get(DispatcherFactory::class)->getRouter($serverName);
        }
        $data = $router->getData();
        array_walk_recursive($data, function ($item) use ($scanAnnotation) {
            if ($item instanceof Handler && ! ($item->callback instanceof Closure)) {
                $prepareHandler = $this->prepareHandler($item->callback);
                if (count($prepareHandler) > 1) {
                    [$controller, $action] = $prepareHandler;
                    $scanAnnotation->scan($controller, $action);
                }
            }
        });
        $scanAnnotation->scanCustomValidationRule();
        $eventDispatcher->dispatch(new AfterEasyValidatorStart($serverConfig, $router));
    }

    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new RuntimeException('Handler not exist.');
    }
}
