<?php
#DocGenerator.php created by stcer@jz at 2024/6/21
namespace j\httpDoc;

use Exception;
use function file_get_contents;

class DocGenerator
{
    protected $defines;

    /**
     * @var DocParser
     */
    protected $parser;

    public $apiUrl = 'api.php';

    public $enable = true;

    public function __construct($defines, $parser = null)
    {
        $this->defines = $defines;
        $this->parser = $parser ?: new DocParser();
    }

    public function handle($action, $params = [])
    {
        $response = new Response();
        if (!$this->enable) {
            $response->setError(new Exception("Not enable", 403));
            $response->send();
            return;
        }

        switch ($action) {
            case 'projects':
                $data = $this->getProjects();
                $response->setData($data);
                break;
            case 'api':
                $reqProject = isset($params['project']) && $params['project']
                    ? $params['project']
                    : 'miniApp';
                $data = $this->getApis($reqProject);
                $response->setData($data);
                break;
            case 'html':
                $html = file_get_contents(__DIR__ . '/template/index.html');
                $html = str_replace("const API_URL = 'api.php'", "const API_URL = '{$this->apiUrl}'", $html);
                $response->setResponseHtml(true);
                $response->setData($html);
                break;
            default:
                $response->setError(new Exception("Invalid request", 404));
        }

        $response->send();
    }

    protected function getProjects(): array
    {
        $projects = [];
        foreach ($this->defines as $key => $define) {
            $projects[] = [
                'key' => $key,
                'name' => $define['name']
            ];
        }
        return $projects;
    }

    protected function getApis($project): array
    {
        $defines = $this->defines;
        if (!isset($defines[$project])) {
           return [];
        }

        $apis = [];
        foreach ($defines[$project]['files'] as $file) {
            $apis[] = $this->parser->parse($file);
        }
        return $apis;
    }
}
