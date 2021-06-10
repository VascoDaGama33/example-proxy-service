<?php namespace app\components\proxyService\requestHandlers;

use app\components\dataService\services\AuthDataService;
use app\components\helpCrunch\HelpCrunch;
use app\components\referral\ReferralComponent;
use app\models\domain\Domain;
use app\models\user\UserActiveService;
use app\models\notification\PartnerNotification;
use Psr\Http\Message\ResponseInterface;

class UserCreateRequestHandler extends BaseRequestHandler
{
    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getPreparedResponse($response)
    {
        $response = parent::getPreparedResponse($response);

        /** @var AuthDataService $dataService */
        $dataService = $this->getDataService();
        if (!empty($response) && !empty($dataService) && $dataService->isSuccessStatusCode($response->getStatusCode())) {
            $userData = $dataService->getResponseData($response);
            if (is_array($userData) && !empty($userData['id'])) {
                // create active service for user
                $userActiveService = $this->getUserActiveServiceInstance()->createUserService($userData['id']);
                if (empty($userActiveService) || empty($userActiveService->id)) {
                    \Yii::error('Error on create active service for user[' . $userData['id'] . '] after registration: ' . json_encode(!empty($userActiveService) ? $userActiveService->getErrors() : ['Model is empty']));
                }
                // create subdomain for user from segment domains list
                $domain = $this->getDomainInstance()->createSubDomainForUser($userData['id']);
                if (empty($domain) || empty($domain->id)) {
                    \Yii::error('Error on create domain for user[' . $userData['id'] . '] after registration: ' . json_encode(!empty($domain) ? $domain->getErrors() : ['Model is empty']));
                }

                // check create referral
                $this->getReferralComponent()->processCreatedUserReferral($userData['id']);

                // add user to HelpCrunch
                $this->getHelpCrunchComponent()->createCustomer($userData);

                //create first notification
                $model = new PartnerNotification();
                $message = [
                    "text" => \Yii::t("app", "Insert tracking code on your website to start tracking leads - <a href='" . \Yii::$app->params["partner_domain"] . "/settings'>here</a>. It may take up to 15 minutes to update the status of code installation."),
                    "is_active" => PartnerNotification::IS_ACTIVE,
                    "user_id" => $userData['id'],
                    "event" => PartnerNotification::EVENT_WAITE_LEAD,
                    "type" => $model->getHintType(PartnerNotification::EVENT_WAITE_LEAD)
                ];
                $model->setAttributes($message);
                $model->save();

            }
        }

        return $response;
    }

    /**
     * @return Domain
     */
    protected function getDomainInstance()
    {
        return new Domain();
    }

    /**
     * @return UserActiveService
     */
    protected function getUserActiveServiceInstance()
    {
        return new UserActiveService();
    }

    /**
     * @return HelpCrunch
     */
    protected function getHelpCrunchComponent()
    {
        return \Yii::$app->helpCrunch;
    }

    /**
     * @return ReferralComponent
     */
    protected function getReferralComponent()
    {
        return \Yii::$app->referral;
    }

}
