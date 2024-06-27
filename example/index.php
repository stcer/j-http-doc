<?php

namespace j\httpDoc;

use function header;
use function preg_replace;
use function var_dump;

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/model/Demo.php');

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers:*');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    //header('Access-Control-Allow-Headers:x-api-version');
    exit('ok'); // finish preflight CORS requests here
}

$defines = [
    'miniApp' => [
        'name' => '小程序接口',
        'env_file' => __DIR__ . '/tests/http-client.env.json',
        'files' => [
            __DIR__ . '/tests/product_app.http',
            __DIR__ . '/tests/interactive_app.http',
        ]
    ],

    'shopAdmin' => [
        'name' => '商家管理',
        'files' => [
            __DIR__ . '/tests/interactive_app.http',
        ]
    ],

    'H5' => [
        'name' => 'H5',
        'desc' => 'H5',
        'files' => [
        ]
    ],
];

$docGenerator = new DocGenerator($defines);
$docGenerator->enable = true;
$docGenerator->apiUrl = 'index.php';
$docGenerator->getParser()
    ->setPlugin(PlugType::PLUGIN_NORMALIZE_REQUEST, function ($content){
        return preg_replace('/#{4,}[^#]+?#+/', '', $content);
    })
    ->setPlugin(PlugType::PLUGIN_NORMALIZE_API, function ($api){
        foreach ($api['query'] ?? [] as $param) {
            if ($param['key'] == 'api') {
                $api['path'] = $param['value'];
            }
        }
        return $api;
    })
;
$docGenerator->handle($_GET['action'] ?? 'html', $_REQUEST);
