<?php


namespace api\controllers;


use common\services\GoogleCalendarService;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;

class GoogleApiController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator']['authMethods'] = [
            HttpBasicAuth::className(),
            HttpBearerAuth::className(),
        ];

        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];

        return $behaviors;
    }

    public function actionCreateEvent()
    {
        $userId = $this->getUser();

        $params = \Yii::$app->request->post();

        $googleApi = new GoogleCalendarService($userId);

        try{
            $event = $googleApi->createDataForCalendarEvent($params);
            $calEvent = $googleApi->createGoogleCalendarEvent($event);
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }

        return [
            'code' => 1,
            'userId' => $userId,
            'eventId'=> $calEvent->getId()
        ];

    }


    public function actionDeleteEvent($eventId)
    {
        $userId = $this->getUser();

        $googleApi = new GoogleCalendarService($userId);

        try{
            $googleApi->deleteGoogleCalendarEvent($eventId);
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }
        return [
            'code' => 1,
            'userId' => $userId,
            'message'=> "Event deleted"
        ];
    }

    public function actionGetEvent($eventId)
    {
        $userId = $this->getUser();

        $googleApi = new GoogleCalendarService($userId);

        try{
            $event = $googleApi->getGoogleCalendarEvent($eventId);
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }

        return [
            'code' => 1,
            'userId' => $userId,
            'eventId' => $event->id,
            'summary'=> $event->summary,
            'location' => $event->location,
            'description' => $event->description,
            'start' =>$event->start,
            'end' => $event->end,
        ];

    }

    public function actionCalendarsList()
    {
        $userId = $this->getUser();

        $googleApi = new GoogleCalendarService($userId);

        try{
            $calendars = $googleApi->calendarList();
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }

        return [
            'code' => 1,
            'userId' => $userId,
            'calendars'=> $calendars
        ];

    }

    private function getUser()
    {
        return \Yii::$app->user->getId();
    }
}