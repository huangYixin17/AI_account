<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

/*
 * This polyfill of hash_equals() is a modified edition of https://github.com/indigophp/hash-compat/tree/43a19f42093a0cd2d11874dff9d891027fc42214
 *
 * Copyright (c) 2015 Indigo Development Team
 * Released under the MIT license
 * https://github.com/indigophp/hash-compat/blob/43a19f42093a0cd2d11874dff9d891027fc42214/LICENSE
 */
if (!function_exists('hash_equals')) {
    defined('USE_MB_STRING') or define('USE_MB_STRING', function_exists('mb_strlen'));

    function hash_equals($knownString, $userString)
    {
        $strlen = function ($string) {
            if (USE_MB_STRING) {
                return mb_strlen($string, '8bit');
            }

            return strlen($string);
        };

        // Compare string lengths
        if (($length = $strlen($knownString)) !== $strlen($userString)) {
            return false;
        }

        $diff = 0;

        // Calculate differences
        for ($i = 0; $i < $length; $i++) {
            $diff |= ord($knownString[$i]) ^ ord($userString[$i]);
        }
        return $diff === 0;
    }
}

class LINEBotTiny
{
    private $channelAccessToken;
    private $channelSecret;
    public $response_DateTime;

    public function __construct($channelAccessToken, $channelSecret)
    {
        $this->channelAccessToken = $channelAccessToken;
        $this->channelSecret = $channelSecret;
    }

    public function parseEvents()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            logMessage('Method not allowed','line_api');
            exit();
        }

        $entityBody = file_get_contents('php://input');

        if (strlen($entityBody) === 0) {
            http_response_code(400);
            logMessage('Missing request body','line_api');
            logMessage("LINEBotTiny：".json_encode($entityBody),'line_api');
            exit();
        }

        if (!hash_equals($this->sign($entityBody), $_SERVER['HTTP_X_LINE_SIGNATURE'])) {
            http_response_code(400);
            logMessage('Invalid signature value','line_api');
            logMessage("LINEBotTiny：".json_encode($entityBody),'line_api');
            exit();
        }

        $data = json_decode($entityBody, true);
        if (!isset($data['events'])) {
            http_response_code(400);
            logMessage('Invalid request body: missing events property','line_api');
            logMessage("LINEBotTiny：".json_encode($data),'line_api');
            exit();
        }

        if(isset($data['events'][0]['source']['userID']) and $data['events'][0]['source']['userId'] == 'Udeadbeefdeadbeefdeadbeefdeadbeef'){
            //測試來的
            http_response_code(200);
            exit();
        }

        
        http_response_code(200);
        return $data['events'];
    }
    private function parseHeaders($headers){
        
        //整理curl response的header
        $head = explode("\r\n",$headers);
        foreach( $head as $k=>$v )
        {
            $t = explode( ':', $v, 2 );
            if( isset( $t[1] ) )
                $head[ trim($t[0]) ] = trim( $t[1] );
        }
        return $head;
    }

    public function replyMessage($message)
    {
        $header = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $ch = curl_init('https://api.line.me/v2/bot/message/reply');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $http_response_header_array = $this->parseHeaders($response);
        
        if(in_array("HTTP/1.1 200 OK",$http_response_header_array) === false ){
            http_response_code(500);
            logMessage('Request failed: '.$response,'line_api');
            logMessage("http_headers：".json_encode($http_response_header_array,JSON_UNESCAPED_UNICODE),'line_api');
            logMessage('message: ' . json_encode($message),'line_api');
            $this->response_DateTime = date('Y-m-d H:i:s');
        }
        else
        {
            try {
                $date = new DateTime($http_response_header_array['Date']);
                $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                $this->response_DateTime = $date->format('Y-m-d H:i:s');
                
            } catch (Exception $e) {
                $this->response_DateTime = date('Y-m-d H:i:s');
            }
        }

    }
    public function pushMessage($message)
    {
        $header = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $ch = curl_init('https://api.line.me/v2/bot/message/push');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        $http_response_header_array = $this->parseHeaders($response);

        if(strpos($http_response_header_array[0], '200') === false){
            http_response_code(500);
            logMessage('Request failed: '.$response,'line_api');
            logMessage("http_headers：".json_encode($http_response_header_array,JSON_UNESCAPED_UNICODE),'line_api');
            logMessage('message: ' . json_encode($message),'line_api');
            $this->response_DateTime = date('Y-m-d H:i:s');
        }
        else
        {
            //獲取response的時間點
            try {
                $date = new DateTime($http_response_header_array['Date']);
                $date->setTimezone(new DateTimeZone('Asia/Taipei'));
                $this->response_DateTime = $date->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $this->response_DateTime = date('Y-m-d H:i:s');
            }
        }

    }
    public function GetContent($messageID)
    {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $header),
            ],
        ]);

        $response = file_get_contents('https://api-data.line.me/v2/bot/message/'.$messageID.'/content', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            logMessage('Request failed: ' . $response,'line_api');
        }

        //獲取response的時間點
        $http_response_header_array = $this->parseHeaders($http_response_header);
        try {
            $date = new DateTime($http_response_header_array['Date']);
            $date->setTimezone(new DateTimeZone('Asia/Taipei'));
            $this->response_DateTime = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->response_DateTime = date('Y-m-d H:i:s');
        }

        return $response;
    }
    public function GetProfile($messageID)
    {
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken,
        );

        $ch = curl_init();
 
        curl_setopt($ch, CURLOPT_URL,'https://api.line.me/v2/bot/profile/'.$messageID);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        curl_close($ch);
        logMessage("get_profile_response：".$response,'line_api');
        
        $this->response_DateTime = date('Y-m-d H:i:s');
        
        return json_decode($response,true);
    }
    public function LinkRichMenu($userID,$richMenuId)
    {
        $header = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->channelAccessToken
        );
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $header),
                'content' => '{}'
            ],
        ]);

        $response = file_get_contents('https://api.line.me/v2/bot/user/'.$userID.'/richmenu/'.$richMenuId, false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log('Request failed: ' . $response);
        }

        //獲取response的時間點
        $http_response_header_array = $this->parseHeaders($http_response_header);
        try {
            $date = new DateTime($http_response_header_array['Date']);
            $date->setTimezone(new DateTimeZone('Asia/Taipei'));
            $this->response_DateTime = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->response_DateTime = date('Y-m-d H:i:s');
        }

        return $response;
    }
    public function unLinkRichMenu($userID){
        $header = array(
            'Authorization: Bearer ' . $this->channelAccessToken
        );
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'header' => implode("\r\n", $header),
            ],
        ]);

        $response = file_get_contents('https://api.line.me/v2/bot/user/'.$userID.'/richmenu/', false, $context);
        if (strpos($http_response_header[0], '200') === false) {
            http_response_code(500);
            error_log('Request failed: ' . $response);
        }

        //獲取response的時間點
        $http_response_header_array = $this->parseHeaders($http_response_header);
        try {
            $date = new DateTime($http_response_header_array['Date']);
            $date->setTimezone(new DateTimeZone('Asia/Taipei'));
            $this->response_DateTime = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->response_DateTime = date('Y-m-d H:i:s');
        }

        return $response;
    }

    private function sign($body)
    {
        $hash = hash_hmac('sha256', $body, $this->channelSecret, true);
        $signature = base64_encode($hash);
        return $signature;
    }
}
