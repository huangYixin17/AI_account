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
    logMessage('input:'.json_encode($event,JSON_UNESCAPED_UNICODE),'line_webhook');

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

    //check user Data
    $db_result = DBSELECT("SELECT user_uuid FROM account_user WHERE user_uuid = '$userID'");
    
    if($db_result['result'] == false){
        $message_array['text'] = '系統異常，請稍後...';
        array_push($reply_array['messages'],$message_array);
        logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE).__LINE__,'line_webhook');
        $line_api->replyMessage($reply_array);
        break;
    }else{
        $result = $db_result['statement']->fetchAll(PDO::FETCH_ASSOC);

        if(empty($result)){
            //insert user Data

            $sql_query = "INSERT INTO account_user (user_uuid,account,current_month)
            VALUES (:user_uuid,:account,:current_month)";
            $column = array(
                ':user_uuid' => $userID,
                ':account' => 0,
                ':current_month' => $data = date('Y-m')
            );

            $db_result = DBINSERT($sql_query,$column);

            if($db_result['result'] != true){
                $message_array['text'] = '系統異常，請稍後...';
                array_push($reply_array['messages'],$message_array);
                logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE).__LINE__,'line_webhook');
                $line_api->replyMessage($reply_array);
                break;
            }
        }
        
    }

    switch ($event['type']) {
        case 'message':

            switch ($event['message']['text']) {
                case 'delete_user_data':

                    $sql_query = "DELETE FROM account_user WHERE user_uuid='$userID'";
                    $db_result = DBDELETE($sql_query);
                    if ($db_result['result'] == false) {
                        $message_array['text'] = '系統異常，請稍後...';
                        array_push($reply_array['messages'], $message_array);
                        logMessage('[ReplyMessage]'.json_encode($reply_array, JSON_UNESCAPED_UNICODE).__LINE__, 'line_webhook');
                        $line_api->replyMessage($reply_array);
                    }else{
                        $sql_query = "DELETE FROM record_account WHERE user_uuid='$userID'";
                        $db_result = DBDELETE($sql_query);
                        $message_array['text'] = '已更新';
                        array_push($reply_array['messages'],$message_array);
                        logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE).__LINE__,'line_webhook');
                        $line_api->replyMessage($reply_array); 
                    }
                    break;
                case 'set_db_month':
                    $sql_query = "UPDATE account_user SET current_month='2021-08-11' WHERE user_uuid='$userID'";
                    $db_result = DBSELECT($sql_query);
                    if($db_result['result'] == false){
                        $message_array['text'] = '系統異常，請稍後...';
                        array_push($reply_array['messages'],$message_array);
                        logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE).__LINE__,'line_webhook');
                        $line_api->replyMessage($reply_array);
                    }else{
                        $message_array['text'] = '已更新';
                        array_push($reply_array['messages'],$message_array);
                        logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE).__LINE__,'line_webhook');
                        $line_api->replyMessage($reply_array); 
                    }
                    break;
                default:
                    $GetDialogflowData = $dialogflow->detect_intent_texts(DIALOGFLOW_PROJECT,$event['message']['text'],$event['source']['userId']);
                    logMessage("[GetDialogflowData]".json_encode($GetDialogflowData,JSON_UNESCAPED_UNICODE),'line_webhook');
        
                    $handleDialogflow = HandleDialogflowResponse($GetDialogflowData,$userID);
                    logMessage('[handleDialogflowResponse]'.json_encode($handleDialogflow,JSON_UNESCAPED_UNICODE),'line_webhook');
                    
                    $GetReturnText = GetReturnText($handleDialogflow,$userID);
                    foreach($GetReturnText as $text){
                        $message_array['text'] = $text;
                        array_push($reply_array['messages'],$message_array);
                    }
        
                    //is new month?
                    $sql_query = "SELECT current_month FROM account_user WHERE user_uuid = '$userID'";
                    $db_result = DBSELECT($sql_query);

                    if($db_result['result'] == false){
                        $message_array['text'] = '系統異常，請稍後...';
                        array_push($reply_array['messages'],$message_array);
                        logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE).__LINE__,'line_webhook');
                        $line_api->replyMessage($reply_array);
                    }else{
                        $result = $db_result['statement']->fetch(PDO::FETCH_ASSOC);
                        $db_month = substr($result['current_month'],0,7);
                        $current_data = date('Y-m-d');
                        $current_y_m = substr($current_data,0,7);
                        logMessage('current_year'.$current_y_m , 'line_webhook' );


                        if( $current_y_m != $db_month){
                            $sql_query = "UPDATE account_user SET current_month='$current_data' WHERE user_uuid = '$userID'";
                            $db_result = DBSELECT($sql_query);

                            if($db_result['result'] == false){
                                $message_array['text'] = '系統異常，請稍後...';
                                array_push($reply_array['messages'],$message_array);
                                logMessage('[ReplyMessage]'.json_encode($reply_array,JSON_UNESCAPED_UNICODE).__LINE__,'line_webhook');
                                $line_api->replyMessage($reply_array);
                            }
                        }
                    }
                    break;
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

