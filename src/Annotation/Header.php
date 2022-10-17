<?php
declare(strict_types=1);

namespace Hyperf\EasyValidator\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Header
{
    /**
     * @param string $key 格式：field|字段名称
     * @param string $rule 格式：required|max:200|test 支持自定义规则 和 hyperf框架所有规则
     */
    public function __construct(string $key, string $rule)
    {

    }
}