<?php
#HttpParser.php created by stcer@jz at 2024/6/21
namespace j\httpDoc;

use ReflectionClass;
use function array_map;
use function array_shift;
use function call_user_func;
use function class_exists;
use function explode;
use function implode;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_replace;
use function str_replace;
use function trim;

class DocParser
{
    protected $plugin = [];

    /**
     * @param array $plugin
     */
    public function __construct(array $plugin= [])
    {
        $this->plugin = $plugin;
    }

    public function parse($filePath)
    {
        $content = file_get_contents($filePath);
        return $this->parseContent($content);
    }

    public function parseContent($content)
    {
        $moduleName = '未知';
        if (preg_match('#//\s*module:\s*(.+)#i', $content, $r)) {
            $moduleName = $r[1];
            $content = preg_replace('#//\s*module:\s*(.+)#i', '', $content);
        }

        $requests = explode('###', $content);
        array_shift($requests);

        $parsedRequests = [];
        foreach ($requests as $req) {
            $lines = array_map('trim', explode("\n", trim($req)));
            $headers = [];
            $body = [];
            $annotation = [];

            $api = [
                'label' => array_shift($lines)
            ];
            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }
                if (preg_match('#(GET|POST)\s+(.+?)$#i', $line, $r)) {
                    $this->parsePath($r, $api);
                } elseif (preg_match('/^[A-Za-z-]+: .+/', $line)) {
                    $headers[] = $line;
                } elseif (preg_match('/#\s*viewType:(.+?)$/', $line, $r)) {
                    $api['viewType'] = trim($r[1]);
                } elseif (preg_match('/#\s*model:(.+?)$/', $line, $r)) {
                    $this->parseModel($r[1], $api);
                } elseif (preg_match('#//(.+?)$#', $line, $r) || preg_match('/#(.+?)$/', $line, $r)) {
                    $annotation[] = trim($r[1]);
                } elseif (!$this->pluginMatch($line, $api)) {
                    $body[] = $line;
                }
            }

            $parsedRequests[] = $api + [
                'headers' => $headers,
                'body' => implode("\n", $body),
                'annotation' => implode("\n", $annotation),
            ];
        }

        return ['name' => $moduleName, 'api' => $parsedRequests];
    }

    protected function parsePath($match, &$api)
    {
        $query = null;
        $method = $match[1];
        $url = str_replace('{{host}}', '', $match[2]);
        $parts = parse_url($url);
        $path = $parts['path'];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);
            foreach ($params as $k => $v) {
                $query[] = ['key' => $k, 'value' => $v, 'type' => 'string'];
            }
        }
        $api['path'] = $path;
        $api['method'] = $method;
        $api['url'] = $url;
        $api['query'] = $query;
    }

    protected function parseModel($model, &$api)
    {
        $model = trim($model);
        if (!class_exists($model)) {
            return;
        }

        $refClass = new ReflectionClass($model);
        $annotation = $refClass->getDocComment();

        $fields = [];
        foreach (explode("\n", $annotation) as $line) {
            if (preg_match('/@property\s+([a-zA-Z]+?)\s+(.+?)[,\s]+(.+)$/', $line, $r)) {
                $label = trim($r[3]);
                if ($label && $label[0] == '[') {
                    continue;
                }

                $fields[] = [
                    'type' => $r[1],
                    'name' => $r[2],
                    'label' => $label
                ];
            }
        }

        $api['model'] = [
            'annotation' => $annotation,
            'fields' => $fields
        ];
    }

    protected function pluginMatch($line, &$api)
    {
        foreach ($this->plugin as $plugin) {
            $rs = call_user_func($plugin, $line, $api);
            if ($rs) {
                return $rs;
            }
        }
        return false;
    }
}
