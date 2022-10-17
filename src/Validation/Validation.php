<?php
declare(strict_types=1);

namespace Hyperf\EasyValidator\Validation;

use Hyperf\EasyValidator\Scan\ValidationManager;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Hyperf\Validation\Contract\PresenceVerifierInterface;
use Hyperf\Validation\Contract\Rule;
use Hyperf\Validation\ValidatorFactory;
use Psr\Container\ContainerInterface;

class Validation
{
    public ContainerInterface $container;

    /** @var ValidatorFactory */
    public ValidatorFactory $factory;

    /**
     * 内置验证规则
     * @var ValidationCustomRule|mixed
     */
    public ValidationCustomRule $customValidateRules;

    /**
     * 自定义规则
     * @var array
     */
    public array $customValidationRuleInstance = [];

    public function __construct()
    {
        $this->container = ApplicationContext::getContainer();
        $this->factory = $this->container->get(ValidatorFactory::class);
        $this->customValidateRules = $this->container->get(ValidationCustomRule::class);
        $containerKeys = ValidationManager::getCustomValidationRuleInstances();
        foreach ($containerKeys as $key) {
            $this->customValidationRuleInstance[] = $this->container->get($key);
        }
    }

    public function check(array $rules, array $data): array
    {
        foreach ($data as $key => $val) {
            if (str_contains((string)$key, '.')) {
                Arr::set($data, $key, $val);
                unset($data[$key]);
            }
        }
        $map = [];
        $realRules = [];
        $whiteData = [];
        foreach ($rules as $key => $rule) {
            if (!is_string($key) && is_array($rule)) {
                $key = array_key_first($rule);
            }
            $fieldExtra = explode('|', $key);
            $field = $fieldExtra[0];
            if (!$rule && Arr::get($data, $field)) {
                $whiteData[$field] = Arr::get($data, $field);
                continue;
            }
            $title = $fieldExtra[1] ?? $fieldExtra[0];
            $_rules = explode('|', $rule);
            foreach ($_rules as &$item) {
                if ($item === 'json') {
                    $item = 'array';
                }
                if (method_exists($this, $item)) {
                    $item = $this->makeCustomRule($title, $item, $this);
                } elseif (method_exists($this->customValidateRules, $item)) {
                    $item = $this->makeCustomRule($title, $item, $this->customValidateRules);
                } else {
                    //自定义验证规则
                    foreach ($this->customValidationRuleInstance as $validationInstance) {
                        if (method_exists($validationInstance, $item)) {
                            $item = $this->makeCustomRule($title, $item, $validationInstance);
                            break 1;
                        }
                    }
                }
                unset($item);
            }
            $realRules[$field] = $_rules;
            $map[$field] = $title;
        }
        $validator = $this->factory->make($data, $realRules, [], $map);
        $verifier = $this->container->get(PresenceVerifierInterface::class);
        $validator->setPresenceVerifier($verifier);
        $fails = $validator->fails();
        $errors = [];
        if ($fails) {
            foreach ($validator->errors()->getMessages() as $column => $messages) {
                $errors[$column] = $messages[0];
            }

            return [
                null,
                $errors,
            ];
        }

        $filterData = array_merge($this->parseData($validator->validated()), $whiteData);
        $realData = [];
        foreach ($filterData as $key => $val) {
            Arr::set($realData, $key, $val);
        }

        $realData = array_map_recursive(static function ($item) {
            return is_string($item) ? trim($item) : $item;
        }, $realData);

        return [
            $realData,
            $errors,
        ];
    }

    public function makeCustomRule($field, $customRule, &$object)
    {
        return new class($field, $customRule, $object) implements Rule {
            public $customRule;
            public $validation;
            public string $error = '%s ';
            public $attribute;
            public $field;

            public function __construct($field, $customRule, &$validation)
            {
                $this->field = $field;
                $this->customRule = $customRule;
                $this->validation = $validation;
            }

            public function passes($attribute, $value): bool
            {
                $this->attribute = $attribute;
                $rule = $this->customRule;
                if (str_contains($rule, ':')) {
                    $rule = explode(':', $rule)[0];
                    $extra = explode(',', explode(':', $rule)[1]);
                    $ret = $this->validation->{$rule}($attribute, $value, $extra);
                    if (is_string($ret)) {
                        $this->error .= $ret;

                        return false;
                    }

                    return true;
                }
                $ret = $this->validation->{$rule}($attribute, $value);
                if (is_string($ret)) {
                    $this->error .= $ret;

                    return false;
                }

                return true;
            }

            public function message(): string
            {
                return sprintf($this->error, $this->field);
            }
        };
    }

    /**
     * Parse the data array, converting -> to dots.
     */
    public function parseData(array $data): array
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);
            }
            if (str_contains((string)$key, '->')) {
                $newData[str_replace('->', '.', $key)] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
    }
}