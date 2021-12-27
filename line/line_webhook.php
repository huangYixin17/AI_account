<?php

require_once(__DIR__.'/../account/ai_config.php');
require_once(__DIR__.'/../account/getReturnText.php');
require_once(__DIR__.'/../account/handleDialogflowResponse.php');
require_once(__DIR__.'/line_api.php');
require_once(__DIR__.'/../dialogflow/dialogflow_class.php');
require 'C:/dialogflow/vendor/autoload.php';


const channelAccessToken = LINE_CHANNEL_ACCESS_TOKEN ;
const channelSecret = LINE_CHANNEL_SECRET ;

header("Content-Type:text/html; charset=utf-8");

date_default_timezone_set('Asia/Taipei');//設置時區

$line_api = new LINEBotTiny(channelAccessToken, channelSecret);
$dialogflow = new dialogflow_class();

foreach ($line_api->parseEvents() as $event) {
    logMessage('input：'.json_encode($event,JSON_UNESCAPED_UNICODE),'line_webhook');

    //判斷來源是否為使用者
    if($event['source']['type'] != 'user') {
        break;
    }

    $message_array = [
        'type' => 'text',
        'text' => ''
    ];

    $push_array = [
        "to"=>$event['source']['userId'],
        "messages"=>[]
    ];

    if(isset($event['replyToken'])){
        $reply_array = ['replyToken' => $event['replyToken'], 'messages' => []];
    }else{
        if($event['type'] != 'unfollow'){
            //刪除好友沒有reply_token
            logMessage('no reply_token userID:'.$event['source']['userId'],'line_webhook');
        }else{
            //unfollow
            logMessage("unfollow",'line_webhook');
        }
        break;
    }

    $userID = $event['source']['userId'];

    switch ($event['type']) {
        case 'message':
            $GetDialogflowData = $dialogflow->detect_intent_texts(DIALOGFLOW_PROJECT,$event['message']['text'],$event['source']['userId']);
            logMessage("[GetDialogflowData]".json_encode($GetDialogflowData,JSON_UNESCAPED_UNICODE),'line_webhook');

            $handleDialogflow = HandleDialogflowResponse($GetDialogflowData);
            logMessage('[handleDialogflowResponse]'.json_encode($handleDialogflow,JSON_UNESCAPED_UNICODE),'line_webhook');
            
            $GetReturnText = GetReturnText($handleDialogflow,$userID);
            foreach($GetReturnText as $text){
                $message_array['text'] = $text;
                array_push($reply_array['messages'],$message_array);
            }
            break;
        case 'follow':
            $message_array['text'] = '嗨你好，歡迎使用記帳機器人，請問你要記什麼帳呢?';
            array_push($reply_array['messages'],$message_array);
            break;
        case 'unfollow':
            
        case 'postback':
            
        default:
            $message_array['text'] = '很抱歉，不支援該類型的輸入。';
            array_push($reply_array['messages'],$message_array);
            break;            
    }

    logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE),'line_webhook');
    $line_api->replyMessage($reply_array);
}