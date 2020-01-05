<?php

namespace filsh\yii2\oauth2server\filters;

use Yii;
use yii\base\Controller;
use filsh\yii2\oauth2server\Module;
use filsh\yii2\oauth2server\exceptions\HttpException;

class ErrorToExceptionFilter extends \yii\base\ActionFilter
{
    /**
     * @inheritdoc
     * @throws HttpException when the request method is not allowed.
     */
    public function afterAction($action, $result)
    {
        $response = Module::getInstance()->getServer()->getResponse();

        $headers = $response->getHttpHeaders();
        if (!empty($headers)) {
            foreach ($headers as $k => $v) {
                Yii::$app->response->headers[$k] = $v;
            }
        }

        $isValid = true;
        if($response !== null) {
            $isValid = $response->isInformational() || $response->isSuccessful() || $response->isRedirection();
        }
        if(!$isValid) {
            throw new HttpException($response->getStatusCode(), $this->getErrorMessage($response), $response->getParameter('error_uri'));
        }

        return $result;
    }
    
    protected function getErrorMessage(\OAuth2\Response $response)
    {
        $message = Module::t('common', $response->getParameter('error_description'));
        if($message === null) {
            $message = Module::t('common', 'An internal server error occurred.');
        }
        return $message;
    }
}
