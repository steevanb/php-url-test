<?php

namespace steevanb\PhpUrlTest\Test;

use Symfony\Component\Yaml\Yaml;

class UrlTest
{
    /** @var Request */
    protected $request;

    /** @var ExpectedResponse */
    protected $expectedResponse;

    /** @var ?Response */
    protected $response;

    /** @var int */
    protected $timeout = 30;

    /** @var bool */
    protected $allowRedirect = false;

    /** @var ?int */
    protected $redirectMin;

    /** @var ?int */
    protected $redirectMax;

    /** @var ?int */
    protected $redirectCount;

    public static function createFromYaml(string $yaml): UrlTest
    {
        if (is_readable($yaml) === false) {
            throw new \Exception('File "' . $yaml . '" does not exist or is not readable.');
        }

        $config = Yaml::parse(file_get_contents($yaml));
        $return = (new static())
            ->setTimeout($config['config']['timeout'] ?? 30)
            ->setAllowRedirect($config['config']['redirect']['allow'] ?? false)
            ->setRedirectMin($config['config']['redirect']['min'] ?? false)
            ->setRedirectMax($config['config']['redirect']['max'] ?? false)
            ->setRedirectCount($config['config']['redirect']['count'] ?? false);

        $return
            ->getRequest()
            ->setUrl($config['request']['url']);

        return $return;
    }


    public function __construct()
    {
        $this->request = new Request();
        $this->expectedResponse = new ExpectedResponse();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getExpectedResponse(): ExpectedResponse
    {
        return $this->expectedResponse;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setAllowRedirect(bool $allowRedirect): self
    {
        $this->allowRedirect = $allowRedirect;

        return $this;
    }

    public function isAllowRedirect(): bool
    {
        return $this->allowRedirect;
    }

    public function setRedirectMin(?int $redirectMin): self
    {
        $this->redirectMin = $redirectMin;

        return $this;
    }

    public function getRedirectMin(): ?int
    {
        return $this->redirectMin;
    }

    public function setRedirectMax(?int $redirectMax): self
    {
        $this->redirectMax = $redirectMax;

        return $this;
    }

    public function getRedirectMax(): ?int
    {
        return $this->redirectMax;
    }

    public function setRedirectCount(?int $redirectCount): self
    {
        $this->redirectCount = $redirectCount;

        return $this;
    }

    public function getRedirectCount(): ?int
    {
        return $this->redirectCount;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function execute()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->getRequest()->getUrl(),
            CURLOPT_PORT => $this->getRequest()->getPort(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $this->getRequest()->getMethod(),
            CURLOPT_HEADER => count($this->getRequest()->getHeaders()) > 0,
            CURLOPT_HTTPHEADER => $this->getRequest()->getHeaders(),
            CURLOPT_REFERER => $this->getRequest()->getReferer(),
            CURLOPT_POSTFIELDS => $this->getRequest()->getPostFields(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->getTimeout(),
            CURLOPT_USERAGENT => $this->getRequest()->getUserAgent()
        ]);
        $response = null;
        try {
            $response = curl_exec($curl);
        } catch (\Exception $e) {
            $this->response = new Response(null, null, null, $e->getMessage());
        }

        if ($response === false) {
            $this->response = new Response(null, null, curl_errno($curl), curl_error($curl));
        } elseif ($response === null) {
            $this->response = new Response(null, null, null, 'Response should not be null.');
        } else {
            $this->response = new Response($curl, $response);
        }
    }
}
