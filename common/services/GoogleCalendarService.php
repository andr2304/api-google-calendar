<?php


namespace common\services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use yii\base\Application;
use yii\helpers\Url;
use Google_Service_Exception;
use yii\web\BadRequestHttpException;


//ЗНАЙШОВ ОСНОВНУ ЧАСТИНУ КОДУ В НЕТІ, НЕ ВСТИГ ЙОГО ПРОРЕФАКТОРИТИ!!!!

class GoogleCalendarService
{

    private $id; // id use to create credentials to different users
    private $calendarId; // calendarId, id of the calendar
    private $client; // google client auth
    public  $credentialsPath; // path to credentials


    public function __construct($id)
    {
        $this->id = $id;
        $this->calendarId = 'primary';
        $this->client = new Google_Client();
        $this->client->setApplicationName('Yii Google Calendar API');
        $this->client->setScopes(Google_Service_Calendar::CALENDAR);
        $this->client->setAuthConfig(__DIR__ .'/../../client_secret.json');
        $this->client->setRedirectUri(Url::to(['/auth-google/auth'],true));
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->credentialsPath = __DIR__ .'/../../google_api_tokens/'.$this->id .'_credentials.json';
    }

    /**
     * generate api accessToken
     */
    public function generateGoogleApiAccessToken(){

        if ($this->checkIfCredentialFileExists($generateToken = true)) {
            $accessToken = json_decode(file_get_contents($this->credentialsPath), true);
        } else {
            // Request authorization from the user.
            if(!isset($_GET['code'])){
                return \Yii::$app->controller->redirect( $this->client->createAuthUrl());
            }else{
                $authCode = $_GET['code'];
                // Exchange authorization code for an access token.
                $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
                // Store the credentials to disk.
                if (!file_exists(dirname($this->credentialsPath))) {
                    mkdir(dirname($this->credentialsPath), 0700, true);
                }
                file_put_contents($this->credentialsPath, json_encode($accessToken));
            }
        }
        $this->client->setAccessToken($accessToken);
        // Refresh the token if it's expired.
        // New check for passing the refresh token down.
        if ($this->client->isAccessTokenExpired()) {
            //Get the old access token
            $oldAccessToken=$this->client->getAccessToken();
            // Get new token with the refresh token
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            // Get the new access
            $accessToken=$this->client->getAccessToken();
            // Push the old refresh token to the new token
            $accessToken['refresh_token']=$oldAccessToken['refresh_token'];
            // Put to the new file
            file_put_contents($this->credentialsPath, json_encode($accessToken));
            //$this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            //file_put_contents($this->credentialsPath, json_encode($this->client->getAccessToken()));
        }

    }

    public function createDataForCalendarEvent($params)
    {
        return array(
            'summary' => $params['name'],
            'location' => 'Kyiv',
            'description' => 'Description here',
            'start' => array(
                'dateTime' =>  $params['start'],//'2018-06-14T09:00:00-07:00',
                'timeZone' => 'America/Los_Angeles',
            ),
            'end' => array(
                'dateTime' => $params['end'], //'2018-06-14T17:00:00-07:00',
                'timeZone' => 'America/Los_Angeles',
            ),
            /*'recurrence' => array(
                'RRULE:FREQ=DAILY;COUNT=2'
            ),
            'attendees' => array(
                array('email' => 'lpage@example.com'),
                array('email' => 'sbrin@example.com'),
            ),
            'reminders' => array(
                'useDefault' => FALSE,
                'overrides' => array(
                    array('method' => 'email', 'minutes' => 24 * 60),
                    array('method' => 'popup', 'minutes' => 10),
                ),
            )*/
        );
    }

    public function createGoogleCalendarEvent($event){

        $this->checkIfCredentialFileExists();
        $this->is_connected();
        try{
            $this->checkAccessToken();
            $service = new Google_Service_Calendar($this->client);
            $calendarId = $this->calendarId;
            $event = new Google_Service_Calendar_Event(array(
                'summary' => $event['summary'],
                'location' => $event['location'],
                'description' => $event['description'],
                'start' =>$event['start'],
                'end' => $event['end'],
                //'recurrence'=>$event['recurrence'],
                //'attendees' => $event['attendees'],
                //'reminders' => $event['reminders']
            ));
            return  $service->events->insert($calendarId, $event);
        }catch (Google_Service_Exception $e){
            echo $e->getMessage();
        }
    }

    public function deleteGoogleCalendarEvent($eventId)
    {
        $this->checkIfCredentialFileExists();
        $this->is_connected();
        try{
            $this->checkAccessToken();
            $service = new Google_Service_Calendar($this->client);
            $calendarId = $this->calendarId;
            if($eventId and $service->events->get($calendarId, $eventId) ){
                $service->events->delete($calendarId, $eventId);
                return true;
            }
        }catch (Google_Service_Exception $e){
            echo $e->getMessage();
        }
    }

    public function getGoogleCalendarEvent($eventId)
    {
        $this->checkIfCredentialFileExists();
        $this->is_connected();
        try{
            $this->checkAccessToken();
            $service = new Google_Service_Calendar($this->client);
            $event = $service->events->get($this->calendarId, $eventId);
            return $event;
        }catch (Google_Service_Exception $e){
            echo $e->getMessage();
        }
    }

    public function calendarList(){
        $calendars = [];
        $this->checkIfCredentialFileExists();
        $this->is_connected();
        try {
            $this->checkAccessToken();
            $service = new Google_Service_Calendar($this->client);
            $calendarList = $service->calendarList->listCalendarList();
            foreach ($calendarList->getItems() as $calendarListEntry) {
                if($calendarListEntry->getAccessRole()=="owner"){
                    $calendars [$calendarListEntry->getId()] = $calendarListEntry->getSummary();
                }
            }
        }catch (Google_Service_Exception $e){
            echo $e->getMessage();
        }
        return $calendars;
    }
    /**
     * Check if access token still valid.
     */
    private function checkAccessToken(){
        $accessToken = json_decode(file_get_contents($this->credentialsPath), true);
        $this->client->setAccessToken($accessToken);
        // Refresh the token if it's expired.
        if ($this->client->isAccessTokenExpired()) {
            //Get the old access token
            $oldAccessToken=$this->client->getAccessToken();
            // Get new token with the refresh token
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            // Get the new access
            $accessToken=$this->client->getAccessToken();
            // Push the old refresh token to the new token
            $accessToken['refresh_token']=$oldAccessToken['refresh_token'];
            // Put to the new file
            file_put_contents($this->credentialsPath, json_encode($accessToken));
            //$this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            //file_put_contents($this->credentialsPath, json_encode($this->client->getAccessToken()));
        }
    }

    /**
     * Check if there is internet connection.
     */
    private function is_connected()
    {
        $connected = @fsockopen("www.google.com", 80);
        //website, port  (try 80 or 443)
        if ($connected){
            $is_conn = true; //action when connected
            fclose($connected);
        }else{
            throw new \RuntimeException('Connection failure');
           // $is_conn = false; //
        }
        return $is_conn;
    }


    public function checkIfCredentialFileExists($generateToken = false)
    {
        if (!file_exists($this->credentialsPath) ) {
            if($generateToken){
                return false;
            }
            throw new BadRequestHttpException('Юзер не пройшов привязку', 400);
        }

        return true;
    }

}