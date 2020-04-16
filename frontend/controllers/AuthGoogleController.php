<?php
namespace frontend\controllers;

use common\services\GoogleCalendarService;
use yii\filters\AccessControl;
use yii\web\Controller;

class AuthGoogleController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionAuth()
    {
        $user_id = \Yii::$app->user->identity->getId();

        $googleApi = new GoogleCalendarService($user_id);

        if(!$googleApi->checkIfCredentialFileExists()) {
            $googleApi->generateGoogleApiAccessToken();
        }

        return $this->render('auth');
    }
}