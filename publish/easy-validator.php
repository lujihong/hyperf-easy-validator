<?php
declare(strict_types=1);

return [
    // enable false 将不会生成 Vo 文件
    'enable' => env('APP_ENV') !== 'production',

    // 自定义验证器错误码、错误描述字段
    'error_code' => 400,
    'http_status_code' => 400,
    'field_error_code' => 'code',
    'field_error_message' => 'message',
    'exception_enable' => false,

    // golbal 节点 为全局性的 注解配置
    // 跟注解相同, 支持 header, query, body, formData
    'global' => [
        // 'header' => [
        //     "x-token|验签" => "required|cb_token"
        // ],
        // 'query' => [
        //     [
        //         'key' => 'test|测试字段',
        //         'rule' => 'required',
        //         'fieldType' => 'string'
        //     ]
        // ]
    ]
];
