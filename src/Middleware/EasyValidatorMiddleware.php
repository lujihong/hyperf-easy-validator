<?php

declare(strict_types=1);

namespace Hyperf\EasyValidator\Middleware;

use FastRoute\Dispatcher;
use Hyperf\Contract\ConfigInterface;
use Hyperf\EasyValidator\Exception\EasyValidatorException;
use Hyperf\EasyValidator\Validation\ValidationApi;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Server\MiddlewareInterface;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;

class EasyValidatorMiddleware implements MiddlewareInterface
{
    /**
     * 验证接口参数
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface
    {
        /** @var Dispatched $dispatched */
        $dispatched = $request->getAttribute(Dispatched::class);
        if ($dispatched->status !== Dispatcher::FOUND) {
            return $handler->handle($request);
        }

        // do not check Closure
        if ($dispatched->handler->callback instanceof \Closure) {
            return $handler->handle($request);
        }

        [$controller, $method] = $this->prepareHandler($dispatched->handler->callback);
        $container = ApplicationContext::getContainer();
        $validationEasyValidator = $container->get(ValidationApi::class);
        $result = $validationEasyValidator->validated($controller, $method);
        if ($result !== true) {
            $config = $container->get(ConfigInterface::class);
            $exceptionEnable = $config->get('easy-validator.exception_enable', false);
            if ($exceptionEnable) {
                $fieldErrorMessage = $config->get('easy-validator.field_error_message', 'message');
                throw new EasyValidatorException($result[$fieldErrorMessage]);
            }
            $httpStatusCode = $config->get('easy-validator.http_status_code', 400);
            $response = $container->get(HttpResponse::class);
            return $response->json($result)->withStatus($httpStatusCode);
        }

        return $handler->handle($request);
    }

    /**
     * @param array|string $handler
     */
    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new \RuntimeException('Handler not exist.');
    }

}
