<?php

namespace filsh\yii2\oauth2server\filters\auth;

use Yii;
use filsh\yii2\oauth2server\Module;
use yii\web\ForbiddenHttpException;

/**
 * Provides a check to ensure the client has passed a valid access
 * token that maps to a null user, aka ones generated for a Client
 * Credentials grant.
 *
 * Since we don't map to a specific user, we cannot inherit
 * from yii\filters\auth\AuthFilter as it requires a valid user.
 */
class ClientTokenAuth extends \yii\base\ActionFilter
{
    /**
     * @var callable a callback that will be called if the access should be denied
     * to the current user. 
     * If not set, [[denyAccess()]] will be called.
     *
     * The signature of the callback should be as follows:
     *
     * ```php
     * function ($action)
     * ```
     *
     * where `$action` is the current [[Action|action]] object.
     */
    public $denyCallback;

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        $server = Module::getInstance()->getServer();
        $server->verifyResourceRequest();
        
        $token = $server->getResourceController()->getToken();
        if (empty($token['user_id'])) {
            return true;
        }

        if ($this->denyCallback !== null) {
            call_user_func($this->denyCallback, $action);
        } else {
            $this->denyAccess();
        }

        return false;
    }

    /**
     * Denies the access of the user.
     * The default implementation will throw a 403 HTTP exception.
     * @throws ForbiddenHttpException 
     */
    protected function denyAccess()
    {
        throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
    }
}
