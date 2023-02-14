<?php
declare(strict_types=1);

namespace Hyperf\EasyValidator\Vo;

use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ServerRequestInterface;

class BaseVo
{
    /**
     * 从request对象中初始化所有属性值
     * @return void
     */
    protected function _init(): void
    {
        try {
            $request = ApplicationContext::getContainer()->get(ServerRequestInterface::class);
            $files = $request->getUploadedFiles();
            foreach ($files as $field1 => $file) {
                $this->_setValue($field1, $file);
            }

            $params = $request->all();
            foreach ($params as $field2 => $value) {
                $this->_setValue($field2, $value);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 获取所有属性，返回数组
     * @param array $filterKeys
     * @return array
     */
    public function getAttributes(array $filterKeys = []): array
    {
        $result = [];
        foreach ($this as $key => $value) {
            if (!in_array($key, $filterKeys, true)) {
                $result[$key] = $value;
            }
        }
        $result['ip'] = $this->getIp();
        return $result;
    }

    /**
     * 获取请求IP
     * @param bool $isInt [是否转换成int类型]
     * @return string
     */
    public function getIp(bool $isInt = false): string|int
    {
        $request = ApplicationContext::getContainer()->get(ServerRequestInterface::class);
        $ip = $request->getServerParams()['remote_addr'] ?? '0.0.0.0';
        $headers = $request->getHeaders();

        if (isset($headers['x-real-ip'])) {
            $ip = $headers['x-real-ip'][0];
        } else if (isset($headers['x-forwarded-for'])) {
            $ip = $headers['x-forwarded-for'][0];
        } else if (isset($headers['http_x_forwarded_for'])) {
            $ip = $headers['http_x_forwarded_for'][0];
        }

        //转换成int类型
        if ($isInt) {
            return ip2long($ip);
        }

        return $ip;
    }

    private function _setValue($field, $value): void
    {
        $str = str_replace('_', ' ', $field);
        $str = str_replace('-', ' ', $str);
        $str = ucwords($str);
        $method = 'set' . str_replace(' ', '', $str);
        $classInfo = ReflectionManager::reflectClass($this::class);
        $type = $classInfo?->getProperty($field)?->getType()?->getName();
        if (method_exists($this, $method)) {
            $value = match ($type) {
                'string' => (string)$value,
                'int' => (int)$value,
                'array' => (array)$value,
                'bool' => (bool)$value,
                'float' => (float)$value,
                default => $value
            };
            $this->$method($value);
        }
    }

}