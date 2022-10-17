## hyperf 框架注解验证器

基于 [Hyperf](https://github.com/hyperf/hyperf) EasyValidator

##### 优点

- 根据注解自动生成Vo类，支持类型限定
- 支持框架数据验证器，自定义验证规则

## 注意

> php >= 8.0

## 安装

```
composer require lujihong/easy-validator
```

## 使用示例

### 控制器

```php
use Hyperf\EasyValidator\Annotation\Body;
use Hyperf\EasyValidator\Annotation\FormData;
use Hyperf\EasyValidator\Annotation\Query;
use Hyperf\EasyValidator\Annotation\Header;

#[Controller(prefix: '/demo')]
class DemoController extends AbstractController
{
    #[
        PostMapping(path: "test"),
        FormData(key: "phone|手机号", rule: "required|phone"),
        FormData(key: "code|验证码", rule: "required|max:6")
    ]
    public function index()
    {

    }

    #[
        PutMapping(path: 'edit'),
        Header(key: 'Token|token', rule: 'required'),
        Body(key: 'test|测试', rule: 'required'),
        Body(key: 'test1|测试1', rule: 'required'),
    ]
    public function edit()
    {
    
    }

    #[
        PostMapping(path: 'fromData'),
        FormData(key: "phone|手机号", rule: "required|phone"),
        FormData(key: "code|验证码", rule: "required|max:6")
    ]
    public function fromData(): bool
    {

    }

    #[
        GetMapping(path: 'find/{id}/and/{in}'),
        Query(key: "id|测试id", rule: "required|numeric"),
        Query(key: "name|测试名称", rule: "required|test") //test为自定义验证规格
    ]
    public function find(int $id, float $in): array
    {
        return ['$id' => $id, '$in' => $in];
    }

}

```

### Vo类
- fieldType，用于生成Vo类的类型限定，不设置此参数则不生成Vo属性
> 支持的值为：string | array | bool | int | float | file

### 验证器
- key 格式：field|字段名称
- rule 验证规则
> rule 支持hyperf框架所有验证

> 内置规则：phone、telephone、identity_card、crontab、class_exist、comma_separated

- 自定义验证规则
> 只需类名称上加上`CustomValidationRule`，并实现对应规则即可
```php
use Hyperf\EasyValidator\Annotation\CustomValidationRule;

#[CustomValidationRule]
class DemoCustomValidationRule
{
    /**
     * 校验错误则返回错误信息, 正确则返回 true
     * @param $attribute
     * @param $value
     * @return bool|string
     */
    public function test($attribute, $value): bool|string
    {
        if (!preg_match('/^1[3456789]{1}\d{9}$/', trim((string)$value))) {
            return '格式不正确';
        }

        return true;
    }
}
```