<?php

declare(strict_types=1);

namespace Hyperf\EasyValidator\Scan;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\EasyValidator\Annotation\Body;
use Hyperf\EasyValidator\Annotation\CustomValidationRule;
use Hyperf\EasyValidator\Annotation\FormData;
use Hyperf\EasyValidator\Annotation\Header;
use Hyperf\EasyValidator\Annotation\Query;
use Hyperf\EasyValidator\Exception\EasyValidatorException;
use Hyperf\EasyValidator\Generator\VoGenerator;
use Psr\Container\ContainerInterface;

use ReflectionException;

class ScanAnnotation extends \JsonMapper
{
    public function __construct(private ContainerInterface $container, private MethodDefinitionCollectorInterface $methodDefinitionCollector)
    {
    }

    /**
     * 扫描控制器中的方法.
     * @param string $className
     * @param string $methodName
     * @throws ReflectionException
     */
    public function scan(string $className, string $methodName): void
    {
        $this->setMethodAttributes($className, $methodName);
    }

    /**
     * 扫描自定义验证器规则
     * @return void
     */
    public function scanCustomValidationRule()
    {
        $classList = AnnotationCollector::getClassesByAnnotation(CustomValidationRule::class);
        foreach ($classList as $className => $value) {
            ValidationManager::setCustomValidationRuleInstance($className);
        }
    }

    /**
     * 设置方法中的参数.
     * @param $className
     * @param $methodName
     * @throws ReflectionException
     */
    private function setMethodAttributes($className, $methodName)
    {
        $ref = ReflectionManager::reflectMethod($className, $methodName);
        $attributes = $ref->getAttributes();
        $fields = [];
        foreach ($attributes as $attribute) {
            $annotationName = $attribute->getName();
            /**
             * 非验证器注解跳过
             */
            if ($annotationName !== Query::class && $annotationName !== FormData::class && $annotationName !== Body::class && $annotationName !== Header::class) {
                continue;
            }

            //验证器字段处理
            $arr = $attribute->getArguments();
            [$field] = explode('|', $arr['key']);
            if (str_contains($field, '.')) {
                [$field] = explode('.', $field);
            }

            if (isset($arr['fieldType'])) {
                if (!str_contains('string,array,bool,int,float,file', $arr['fieldType'])) {
                    throw new EasyValidatorException("[{$className}::{$methodName} - {$annotationName}] annotation fieldType value invalid, Only supports: string,array,bool,int,float,object,file");
                }

                if (!in_array($field, $fields, true)) {
                    $fields[$field] = ['name' => $field, 'type' => $arr['fieldType']];
                }
            }

            if ($annotationName === Query::class) {
                ValidationManager::setQueryRules($className, $methodName, $arr['key'], $arr['rule']);
            }

            if ($annotationName === FormData::class) {
                ValidationManager::setFormDataRules($className, $methodName, $arr['key'], $arr['rule']);
            }

            if ($annotationName === Body::class) {
                ValidationManager::setBodyRules($className, $methodName, $arr['key'], $arr['rule']);
            }

            if ($annotationName === Header::class) {
                ValidationManager::setHeaderRules($className, $methodName, $arr['key'], $arr['rule']);
            }
        }

        if ($fields) {
            $fields = array_values($fields);
            [$path, , $class] = explode('\\', $className);
            $class = str_replace("Controller", '', $class);
            $namespace = $path . '\Vo\\'.$class;
            $path = BASE_PATH . '/' . strtolower($path) . '/Vo/' . $class . '/';
            $className = $class . ucfirst($methodName) . 'Vo';
            $config = $this->container->get(ConfigInterface::class);
            if ($config->get('easy-validator.enable')) {
                new VoGenerator($className, $fields, $namespace, $path);
            }
        }
    }
}
