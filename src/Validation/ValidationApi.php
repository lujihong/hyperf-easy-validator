<?php
declare(strict_types=1);

namespace Hyperf\EasyValidator\Validation;

use Hyperf\Contract\ConfigInterface;
use Hyperf\EasyValidator\Scan\ValidationManager;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Context\Context;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class ValidationApi
{
    public Validation $validation;
    public ContainerInterface $container;
    public ConfigInterface $config;

    public function __construct()
    {
        $this->validation = make(Validation::class);
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
    }

    public function validated($controller, $method): bool|array
    {
        $headerRules = ValidationManager::getHeaderRules($controller, $method);
        $queryRules = ValidationManager::getQueryRules($controller, $method);
        $bodyRules = ValidationManager::getBodyRules($controller, $method);
        $formDataRules = ValidationManager::getFormDataRules($controller, $method);

        if (!array_filter(compact('headerRules', 'queryRules', 'bodyRules', 'formDataRules'))) {
            return true;
        }

        $errorCode = $this->config->get('easy-validator.error_code', -1);
        $fieldErrorCode = $this->config->get('easy-validator.field_error_code', 'code');
        $fieldErrorMessage = $this->config->get('easy-validator.field_error_message', 'message');
        $request = $this->container->get(ServerRequestInterface::class);

        if ($headerRules) {
            $headers = $request->getHeaders();
            $headers = array_map(static function ($item) {
                return $item[0];
            }, $headers);
            $realHeaders = [];
            foreach ($headers as $key => $val) {
                $realHeaders[implode('-', array_map('ucfirst', explode('-', $key)))] = $val;
            }

            [$data, $error] = $this->check($headerRules, $realHeaders);
            if ($data === null) {
                return [
                    'success' => false,
                    $fieldErrorCode => $errorCode,
                    $fieldErrorMessage => $error
                ];
            }
        }

        if ($queryRules) {
            [$data, $error] = $this->check($queryRules, $request->getQueryParams());
            if ($data === null) {
                return [
                    'success' => false,
                    $fieldErrorCode => $errorCode,
                    $fieldErrorMessage => $error
                ];
            }
            Context::set(ServerRequestInterface::class, $request->withQueryParams($data));
        }

        if ($bodyRules) {
            [$data, $error] = $this->check($bodyRules, $request->getParsedBody());
            if ($data === null) {
                return [
                    'success' => false,
                    $fieldErrorCode => $errorCode,
                    $fieldErrorMessage => $error
                ];
            }
            Context::set(ServerRequestInterface::class, $request->withBody(new SwooleStream(json_encode($data))));
        }

        if ($formDataRules) {
            [$data, $error] = $this->check($formDataRules, array_merge($request->getUploadedFiles(), $request->getParsedBody()));
            if ($data === null) {
                return [
                    'success' => false,
                    $fieldErrorCode => $errorCode,
                    $fieldErrorMessage => $error
                ];
            }
            Context::set(ServerRequestInterface::class, $request->withParsedBody($data));
        }

        return true;
    }

    public function check($rules, $data)
    {
        [$data, $error] = $this->validation->check($rules, $data);
        return [$data, $error];
    }
}