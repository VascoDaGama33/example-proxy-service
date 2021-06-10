<?php namespace app\components\proxyService\requestHandlers;

use app\components\proxyService\streams\CustomPhpInputStream;
use Laminas\Diactoros\ServerRequest;

class AuthTokenRequestHandler extends BaseRequestHandler
{

    /**
     * @param ServerRequest $serverRequest
     * @return ServerRequest
     */
    public function getPreparedRequest(ServerRequest $serverRequest)
    {
        $serverRequest = parent::getPreparedRequest($serverRequest);

        $additionalParams = [];
        if (
            !empty($this->serviceData)
            && !empty($this->serviceData->serviceConfig)
            && !empty($this->serviceData->serviceConfig['auth_client_secret'])
        ) {
            $additionalParams['client_secret'] = $this->serviceData->serviceConfig['auth_client_secret'];
        }

        if (!empty($additionalParams)) {
            $stream = $this->getCustomPhpInputStream();
            $stream->setAdditionalParams($additionalParams);
            $serverRequest = $serverRequest->withBody($stream);
        }

        return $serverRequest;
    }

    /**
     * @return CustomPhpInputStream
     */
    protected function getCustomPhpInputStream()
    {
        return new CustomPhpInputStream();
    }

}