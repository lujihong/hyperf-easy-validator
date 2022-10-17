<?php

declare(strict_types=1);

namespace Hyperf\EasyValidator\Scan;

use Hyperf\EasyValidator\Validation\ValidationCustomRule;
use Hyperf\Utils\ApplicationContext;

class ValidationManager
{
    protected static array $queryRules = [];
    protected static array $formDataRules = [];
    protected static array $headerRules = [];
    protected static array $bodyRules = [];
    protected static array $validationRuleInstances = [];

    public function __construct()
    {
        ApplicationContext::getContainer()->get(ValidationCustomRule::class);
    }

    public static function getCustomValidationRuleInstances(): array
    {
        return static::$validationRuleInstances;
    }

    public static function setCustomValidationRuleInstance(string $className): void
    {
        ApplicationContext::getContainer()->get($className);
        static::$validationRuleInstances[] = $className;
    }

    public static function setQueryRules(string $className, string $methodName, string $key, string $rule): void
    {
        $className = trim($className, '\\');
        static::$queryRules[$className . '.' . $methodName][$key] = $rule;
    }

    public static function getQueryRules(string $className, string $methodName): array
    {
        $className = trim($className, '\\');
        return static::$queryRules[$className . '.' . $methodName] ?? [];
    }

    public static function setFormDataRules(string $className, string $methodName, string $key, string $rule): void
    {
        $className = trim($className, '\\');
        static::$formDataRules[$className . '.' . $methodName][$key] = $rule;
    }

    public static function getFormDataRules(string $className, string $methodName): array
    {
        $className = trim($className, '\\');
        return static::$formDataRules[$className . '.' . $methodName] ?? [];
    }

    public static function setHeaderRules(string $className, string $methodName, string $key, string $rule): void
    {
        $className = trim($className, '\\');
        static::$headerRules[$className . '.' . $methodName][$key] = $rule;
    }

    public static function getHeaderRules(string $className, string $methodName): array
    {
        $className = trim($className, '\\');
        return static::$headerRules[$className . '.' . $methodName] ?? [];
    }

    public static function setBodyRules(string $className, string $methodName, string $key, string $rule): void
    {
        $className = trim($className, '\\');
        static::$bodyRules[$className . '.' . $methodName][$key] = $rule;
    }

    public static function getBodyRules(string $className, string $methodName): array
    {
        $className = trim($className, '\\');
        return static::$bodyRules[$className . '.' . $methodName] ?? [];
    }
}
