<?php
# DocParserTest.php
/**
 * User: Administrator
 * Date: 2024/6/26
 * Time: 9:31
 */
namespace j\httpDoc;

use PHPUnit\Framework\TestCase;
use function strpos;

class DocParserTest extends TestCase
{
    protected $source = <<<STR
// module: 产品信息
# module desc line1
# module desc line2

### 访问记录添加
# test
# info_type: product=产品，article=图文
POST {{host}}/api/interactive/access/add?token={{token-ly}}
X-APP: {{appId}}
content-type: application/json

{
  "info_type": "product",
  "info_id": 1,
  "path": "page/index"
}

### 产品列表
# Request: sk: 搜索关键字, cid: number 类别id
# Response: array<{id: number, name: string, price: number, image: string, category: string}>
# model: j\httpDoc\example\model\Product
# viewType: list
GET {{host}}/api/product/search?sk=&cid=
Authorization: {{token}}
X-APP: {{appId}}
STR;

    public function testParseContent()
    {
        $parser = new DocParser();
        $result = $parser->parseContent($this->source);
        $this->assertTrue($result['name'] == '产品信息');
        $this->assertTrue(count($result['api']) == 2);
        $this->assertTrue($result['api'][0]['label'] == '访问记录添加');
        $this->assertTrue(
            strpos($result['desc'], 'module desc line1') > -1
            && strpos($result['desc'], 'module desc line2') > -1
        );
    }
}
