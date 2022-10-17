<?php
declare(strict_types=1);

namespace Hyperf\EasyValidator\Vo;

use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\ApplicationContext;
use Psr\Http\Message\ServerRequestInterface;

class BaseVo
{
    /**
     * @var \ReflectionClass
     */
    private $classInfo;

    public function __construct()
    {
        $this->classInfo = ReflectionManager::reflectClass($this::class);
    }

    /**
     * 从request对象中初始化所有属性值
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function _init(): void
    {
        $request = ApplicationContext::getContainer()->get(ServerRequestInterface::class);
        $files = $request->getUploadedFiles();
        foreach ($files as $field1 => $file) {
            $this->_setValue($field1, $file);
        }

        $params = $request->all();
        foreach ($params as $field2 => $value) {
            $this->_setValue($field2, $value);
        }

        unset($this->classInfo);
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
        return $result;
    }

    private function _setValue($field, $value): void
    {
        $method = 'set' . ucfirst($field);
        $type = $this->classInfo?->getProperty($field)?->getType()?->getName();
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