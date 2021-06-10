<?php namespace app\components\proxyService;

use app\components\proxyService\models\ServiceData;

class ProxyServiceConfig
{

    /**
     * @param string $uri
     * @param string|null $serviceName
     * @return string
     */
    public function getClearUri($uri, $serviceName = null)
    {
        $uri = strtolower($uri);

        // remove backend from start of uri
        $backendPosition = strpos($uri, '/backend/');
        if ($backendPosition === 0) {
            $uri = substr_replace($uri, '/', $backendPosition, strlen('/backend/'));
        }

        // remove service name from start of uri
        if (!empty($serviceName)) {
            $serviceNamePart = '/' . trim(strtolower($serviceName), '/') . '/';
            $servicePosition = strpos($uri, $serviceNamePart);
            if ($servicePosition === 0) {
                $uri = substr_replace($uri, '/', $servicePosition, strlen($serviceNamePart));
            }
        }

        return $uri;
    }

    /**
     * @return array
     */
    public function getAllServicesConfig()
    {
        return \Yii::$app->params['services'];
    }

    /**
     * Get data with configs for service
     *
     * @param string $uri
     * @return ServiceData|null
     */
    public function getServiceDataByUri($uri)
    {
        $result = null;

        $serviceName        = $this->getServiceNameFromUri($uri);
        $allServicesConfig  = $this->getAllServicesConfig();
        if (!empty($serviceName) && !empty($allServicesConfig[$serviceName])) {
            $clearUri   = $this->getClearUri($uri, $serviceName);
            $service    = $allServicesConfig[$serviceName];
            if (
                !empty($clearUri)
                && !empty($service['allowed_urls'])
                && array_key_exists($clearUri, $service['allowed_urls'])
            ) {
                $result = $this->getServiceData();
                $result->serviceName        = $serviceName;
                $result->serviceConfig      = $service;
                $result->urlServiceConfig   = $service['allowed_urls'][$clearUri];
                $result->serviceClearUri    = !empty($result->urlServiceConfig['uri'])
                    ? $result->urlServiceConfig['uri']
                    : $clearUri;
            }
        }

        return $result;
    }

    /**
     * Parse service name from uri
     *
     * @param string $uri
     * @return string|null
     */
    protected function getServiceNameFromUri($uri)
    {
        $clearUri = $this->getClearUri($uri);
        $uriParts = explode('/', trim($clearUri, '/'));
        return !empty($uriParts[0]) ? strtolower($uriParts[0]) : null;
    }

    /**
     * @return ServiceData
     */
    protected function getServiceData()
    {
        return new ServiceData();
    }

}