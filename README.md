Example of proxy component in Yii2
============================

### Description:
Yii2 component execute proxy requests to another services and return response.

### Required:
 - Yii2
 - composer package ```"jenssegers/proxy": "dev-master"```

### Example:
Example run proxy service in controller:
```
public function actionIndex(ProxyService $proxyService)
{
    $response = $proxyService->executeProxyRequest();

    // Output response to the browser.
    $proxyService->getSapiEmitter()->emit($response);
    exit(0);
}
```