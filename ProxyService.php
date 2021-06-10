<?php namespace app\components\proxyService;

use app\components\proxyService\requestHandlers\BaseRequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Proxy\Adapter\Guzzle\GuzzleAdapter;
use Proxy\Filter\RemoveEncodingFilter;
use Proxy\Proxy;
use yii\web\NotFoundHttpException;

class ProxyService
{

    /**
     * @return \Psr\Http\Message\ResponseInterface
     * @throws NotFoundHttpException
     */
    public function executeProxyRequest()
    {
        // Create a PSR7 request based on the current browser request.
        $serverRequest = $this->getServerRequest();
        $serverParams = $serverRequest->getServerParams();

        $requestUri = $this->getRequestUri($serverParams);
        $serviceData = !empty($requestUri) ? $this->getProxyServiceConfig()->getServiceDataByUri($requestUri) : null;

        if (empty($serviceData)) {
            throw new NotFoundHttpException();
        }

        $requestHandler = $this->getRequestHandler($serviceData);

        $domain         = $requestHandler->getRequestDomain();
        $serverRequest  = $requestHandler->getPreparedRequest($serverRequest);

        if (empty($domain)) {
            throw new NotFoundHttpException();
        }

        $guzzle = $this->getGuzzleClient();
        $proxy = $this->getProxy($this->getGuzzleAdapter($guzzle));
        // Add a response filter that removes the encoding headers.
        $proxy->filter(new RemoveEncodingFilter());

        try {
            // Forward the request and get the response.
            $response = $proxy->forward($serverRequest)
                ->to($domain);
            $response = $requestHandler->getPreparedResponse($response);
        } catch (RequestException $e) {
            \Yii::error('Error proxy request for url ' . $domain . $serverRequest->getUri()->getPath() .  ': ' . $e->getMessage());
            $response = $e->getResponse();
        }

        return $response;
    }

    /**
     * Get request clear uri
     *
     * @param array $serverParams
     * @return string|null
     */
    protected function getRequestUri($serverParams)
    {
        $uri = null;

        if (!empty($serverParams)) {
            if (!empty($serverParams['REDIRECT_URL'])) {
                $uri = $serverParams['REDIRECT_URL'];
            } elseif (!empty($serverParams['REQUEST_URI'])) {
                $urlParts = explode('?', $serverParams['REQUEST_URI'], 2);
                !empty($urlParts[0]) && $uri = $urlParts[0];
            }
        }

        return $uri;
    }

    /**
     * @return \Laminas\HttpHandlerRunner\Emitter\SapiEmitter
     */
    public function getSapiEmitter()
    {
        return new SapiEmitter;
    }


    /**
     * @return ServerRequest
     */
    public function getServerRequest()
    {
        return ServerRequestFactory::fromGlobals();
    }

    /**
     * @param array $config
     *
     * @return Client
     */
    protected function getGuzzleClient($config = [])
    {
        return new Client($config);
    }

    /**
     * @param Client $client
     * @return GuzzleAdapter
     */
    protected function getGuzzleAdapter($client)
    {
        return new GuzzleAdapter($client);
    }

    /**
     * @param GuzzleAdapter $adapter
     * @return Proxy
     */
    protected function getProxy($adapter)
    {
        return new Proxy($adapter);
    }

    /**
     * @return ProxyServiceConfig
     */
    protected function getProxyServiceConfig()
    {
        return new ProxyServiceConfig();
    }

    /**
     * Get request handler for process request and response
     *
     * @param $serviceData
     * @return BaseRequestHandler
     */
    public function getRequestHandler($serviceData)
    {
        $requestHandler = null;

        if (
            !empty($serviceData)
            && !empty($serviceData->urlServiceConfig)
            && !empty($serviceData->urlServiceConfig['request_handler'])
        ) {
            $className = '\app\components\proxyService\requestHandlers\\' . $serviceData->urlServiceConfig['request_handler'];
            $requestHandler = new $className($serviceData);
        } else {
            $requestHandler = new BaseRequestHandler($serviceData);
        }

        return $requestHandler;
    }
}