<?php


namespace api\controllers;


use common\services\GoogleCalendarService;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;

class GoogleApiController extends Controller
{

    public function actionCreateEvent()
    {
        $params = \Yii::$app->request->post();

        $googleApi = new GoogleCalendarService($params['id']);

        try{
            $event = $googleApi->createDataForCalendarEvent($params);
            $calEvent = $googleApi->createGoogleCalendarEvent($event);
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }

        return [
            'code' => 1,
            'eventId'=> $calEvent->getId()
        ];

    }


    public function actionDeleteEvent($id, $eventId)
    {
        $googleApi = new GoogleCalendarService($id);

        try{
            $googleApi->deleteGoogleCalendarEvent($eventId);
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }
        return [
            'code' => 1,
            'message'=> "Event deleted"
        ];
    }

    public function actionGetEvent($id, $eventId)
    {
        $googleApi = new GoogleCalendarService($id);

        try{
            $event = $googleApi->getGoogleCalendarEvent($eventId);
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }

        return [
            'code' => 1,
            'id' => $event->id,
            'summary'=> $event->summary,
            'location' => $event->location,
            'description' => $event->description,
            'start' =>$event->start,
            'end' => $event->end,
        ];

    }

    public function actionCalendarsList($id)
    {
        $googleApi = new GoogleCalendarService($id);

        try{
            $calendars = $googleApi->calendarList();
        }catch (\Exception $exception){
            throw new BadRequestHttpException($exception->getMessage(), null);
        }

        return [
            'code' => 1,
            'calendars'=> $calendars
        ];

    }
}