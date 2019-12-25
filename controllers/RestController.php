<?php

namespace filsh\yii2\oauth2server\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use filsh\yii2\oauth2server\filters\ErrorToExceptionFilter;
use filsh\yii2\oauth2server\models\OauthAccessTokens;
use yii\filters\Cors;

class RestController extends \yii\rest\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'exceptionFilter' => [
                'class' => ErrorToExceptionFilter::className()
            ],
            'corsFilter' => [
                'class' => Cors::className() // some custom config inside the class
            ],
        ]);
    }

    public function actionOptions()
    {
        Yii::$app->getResponse()->getHeaders()->set('Allow', implode(', ', ['OPTIONS', 'POST']));
    }


    public function actionToken()
    {
        /** @var $response \OAuth2\Response */
        $response = $this->module->getServer()->handleTokenRequest();
        return $response->getParameters();
    }

    public function actionRevoke()
    {
        /** @var $response \OAuth2\Response */
        $response = $this->module->getServer()->handleRevokeRequest();
        return $response->getParameters();
    }

    public function actionUserInfo()
    {
        $response = $this->module->getServer()->handleUserInfoRequest();
        return $response->getParameters();
    }

    public function actionIntrospect()
    {
        $server = Module::getInstance()->getServer();
        $server->verifyResourceRequest();

        if (!Yii::$app->request->post('token'))
        {
            $message = Yii::t('oauth2server', 'Missing parameter: "token" is required');
            if($message === null) {
                $message = Yii::t('yii', 'An internal server error occurred.');
            }
            throw new \yii\web\HttpException(400, $message);

        }

        $response["active"] = false;

        $token = OauthAccessTokens::findOne(["access_token"=>Yii::$app->request->post('token')]);
        if ($token)
        {
            $expires = strtotime($token->expires);
            if (time() < $expires)
                $response["active"] = true;
            $response["scope"] = $token->scope;
            $response["user_id"] = $token->user_id;
            $response["client_id"] = $token->client_id;
            $response["exp"] = $expires;
        }

        return $response;
    }

}
