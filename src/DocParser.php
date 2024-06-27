<?php
#HttpParser.php created by stcer@jz at 2024/6/21
namespace j\httpDoc;

use ReflectionClass;
use function array_filter;
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

    public function setPlugin($type, callable $plugin)
    {
        $this->plugin[$type][] = $plugin;
    }

    public function resetPlugin()
    {
        $this->plugin = [];
    }

    public function parse($filePath)
    {
        $content = file_get_contents($filePath);
        return $this->parseContent($content);
    }

    public function parseContent($content)
    {
        $this->normalizeContent($content);
        $requests = explode('###', $content);

        // request name and desc
        $moduleMeta = trim(array_shift($requests));
        list($moduleName, $moduleDesc) = $this->parseModuleMeta($moduleMeta);

        $apis = [];
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

            if (isset($api['path']) && $api['path']) {
                $api = $api + [
                    'headers' => $headers,
                    'body' => implode("\n", $body),
                    'annotation' => implode("\n", $annotation),
                ];
                $apis[] = $this->normalizeApi($api);
            }
        }

        return [
            'name' => $moduleName,
            'desc' => $moduleDesc,
            'api' => $apis
        ];
    }

    protected function normalizeContent(& $content)
    {
        $this->pluginNormalizeRequest($content);
    }

    protected function normalizeApi($api)
    {
        $api['path'] = str_replace('{{host}}', '', $api['path']);
        return $api;
    }

    /**
     * @param string $moduleMeta
     * @return array
     */
    protected function parseModuleMeta(string $moduleMeta): array
    {
        $name = '未知';
        $desc = '';
        if (preg_match('#//\s*module:\s*(.+)#i', $moduleMeta, $r)) {
            $name = $r[1];
            $lines = explode("\n", $moduleMeta);
            array_shift($lines); // delete name
            $lines = array_map(function ($line){
                return trim(preg_replace('/^\s*(\/\/|#)\s*/', '', $line));
            }, $lines);
            $desc = implode("\n", array_filter($lines));
        }
        return array($name, $desc);
    }

    protected function parsePath($match, &$api)
    {
        $query = null;
        $method = $match[1];
//        $url = str_replace('{{host}}', '', $match[2]);
        $url = $match[2];
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
        $plugins = $this->plugin[PlugType::PLUGIN_MATCH] ?? [];
        foreach ($plugins as $plugin) {
            $rs = call_user_func($plugin, $line, $api);
            if ($rs) {
                return $rs;
            }
        }
        return false;
    }

    protected function pluginNormalizeRequest(&$request)
    {
        $plugins = $this->plugin[PlugType::PLUGIN_NORMALIZE_REQUEST] ?? [];
        foreach ($plugins as $plugin) {
            $request = call_user_func($plugin, $request);
        }
    }
}
