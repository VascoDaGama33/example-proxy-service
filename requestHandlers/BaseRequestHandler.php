<?php namespace app\components\proxyService\requestHandlers;

use app\components\dataService\DataServiceFactory;
use app\components\dataService\services\AbstractDataService;
use app\components\proxyService\models\ServiceData;
use app\components\user\UserIdentity;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use app\helpers\AHttpRequest;

class BaseRequestHandler
{

    /**
     * @var ServiceData|null
     */
    protected $serviceData = null;


    /**
     * @param ServiceData|null $serviceData
     */
    public function __construct($serviceData)
    {
        $this->serviceData = $serviceData;
    }

    /**
     * @param ServerRequest $serverRequest
     * @return ServerRequest
     */
    public function getPreparedRequest(ServerRequest $serverRequest)
    {
        $serverRequest = $this->prepareRequestUrl($serverRequest);
        $serverRequest = $this->prepareRequestHeaders($serverRequest);

        return $serverRequest;
    }


    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getPreparedResponse($response)
    {
        return $response;
    }

    /**
     * @return string
     */
    public function getRequestDomain()
    {
        return !empty($this->serviceData) ? rtrim($this->serviceData->serviceConfig['domain_url'], '/') : '';
    }

    /**
     * Prepare proxy url with params
     *
     * @param ServerRequest $serverRequest
     * @return ServerRequest
     */
    protected function prepareRequestUrl(ServerRequest $serverRequest)
    {
        if (!empty($this->serviceData)) {
            $preparedServerRequestUri = $serverRequest->getUri()->withPath('/' . trim($this->serviceData->serviceClearUri, '/'));

            $query = [];
            isset($this->serviceData->urlServiceConfig['additional_url_params']) && $query = array_merge($query, $this->serviceData->urlServiceConfig['additional_url_params']);

            if (!empty($this->serviceData->serviceConfig['is_add_in_user_id'])) {
                $invisibleUserId = $this->getInvisibleUserId();
                !empty($invisibleUserId) && $query['in_user_id'] = $invisibleUserId;
            }

            if (!empty($query)) {
                $currentQuery = $preparedServerRequestUri->getQuery();
                $preparedServerRequestUri = $preparedServerRequestUri->withQuery($this->mergeUrlQueryParams($currentQuery, $this->getUrlQueryParams($query)));
            }

            $serverRequest = $serverRequest->withUri($preparedServerRequestUri);
        }

        return $serverRequest;
    }

    /**
     * @param ServerRequest $serverRequest
     * @return ServerRequest
     */
    protected function prepareRequestHeaders(ServerRequest $serverRequest)
    {
        // add header with user data
        $serverRequest = $serverRequest->withHeader('X-Current-User-IP', (new AHttpRequest())->getUserHostAddress());
        return $serverRequest;
    }



    /**
     * @return AbstractDataService|null
     */
    protected function getDataService()
    {
        $dataService = null;

        if (!empty($this->serviceData) && !empty($this->serviceData->serviceConfig['service_class'])) {
            $dataService = $this->getDataServiceFactory()->getDataService($this->serviceData->serviceConfig['service_class']);
        }

        return $dataService;
    }

    /**
     * @return DataServiceFactory
     */
    protected function getDataServiceFactory()
    {
        return new DataServiceFactory();
    }

    /**
     * @return int|null
     */
    protected function getInvisibleUserId()
    {
        /** @var UserIdentity $user */
        $user = \Yii::$app->user->identity;

        return !empty($user) && !empty($user->isInvisibleMode()) ? $user->id : null;
    }

    /**
     * Merge GET params for url
     *
     * @param string $query
     * @param string $additionalQuery
     * @return string
     */
    protected function mergeUrlQueryParams($query, $additionalQuery)
    {
        parse_str(!empty($query) ? $query : '', $queryParams);
        parse_str(!empty($additionalQuery) ? $additionalQuery : '', $additionalQueryParams);

        return http_build_query(array_merge($queryParams, $additionalQueryParams));
    }

    /**
     * Prepare url query params
     *
     * @param array $additionalUrlParams
     * @return string
     */
    protected function getUrlQueryParams($additionalUrlParams)
    {
        $query = '';

        if (is_array($additionalUrlParams)) {
            $queryParams = [];
            foreach ($additionalUrlParams as $k => $v) {
                $queryParams[] = $k . '=' . $v;
            }
            $query = implode('&', $queryParams);
        }

        return $query;
    }

}
