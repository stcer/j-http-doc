<?php

namespace j\httpDoc;

use Throwable;

/**
 * Class Response
 */
class Response
{
    protected $code = 200;
    protected $data;
    protected $isRaw = false;
    protected $headers = [];

    /**
     * @var \Exception
     */
    protected $error;

    public function __construct($data = [], $isRaw = false)
    {
        if ($data) {
            $this->data = $data;
        }
        $this->isRaw = $isRaw;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setError(Throwable $e)
    {
        $this->error = $e;
        $this->data = $e->getMessage();
        $this->code = $e->getCode();
    }

    public function isError(): bool
    {
        return isset($this->error) && $this->error;
    }

    public function getError()
    {
        return $this->error;
    }

    protected function toUtf8($data)
    {
        return $data;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setHeader($key, $value = null): Response
    {
        if (is_array($key)) {
            $this->headers = array_merge($this->headers, $key);
        } else {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    protected function sendHeader()
    {
        foreach ($this->headers as $key => $value) {
            header("{$key}:{$value}");
        }
    }

    public function send()
    {
        if ($this->responseHtml) {
            echo $this->data;
            return;
        }

        if (!$this->isRaw) {
            if ($this->code == 200) {
                $data = [
                    'code' => $this->code,
                    'data' => $this->data
                ];
            } else {
                $e = $this->error;
                $eDetail = $e->errors ?? [];

                if (isset($eDetail) && !$eDetail) {
                    $eDetail = null;
                }

                $data = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'errors' => $eDetail
                ];
            }
        } else {
            $data = $this->data;
        }

        $json = json_encode($this->toUtf8($data));
        $json = str_replace('"{}"', '{}', $json);

        if (isset($_REQUEST['callback']) && ($callback = $_REQUEST['callback']) && is_string($callback)) {
            $this->setHeader('Content-Type', 'application/x-javascript;charset=utf-8');
            $this->sendHeader();
            echo $callback ."({$json});";
        } else {
            $this->setHeader('Content-Type', 'application/json;charset=utf-8');
            $this->sendHeader();
            echo $json;
        }
    }

    /**
     * @param bool $isRaw
     */
    public function setIsRaw(bool $isRaw)
    {
        $this->isRaw = $isRaw;
    }

    protected $responseHtml = false;

    public function setResponseHtml(bool $responseHtml)
    {
        $this->responseHtml = $responseHtml;
    }
}
