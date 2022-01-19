<?php


function HandleDialogflowResponse($dialogflowData,$userID,$mysql){
    $checkDataResponse = checkData($dialogflowData);
    $logName = 'handleDialogflowResponse';
    if($checkDataResponse['result'] != true){
        $response = array('type' => 'error');
        logMessage('checkData error'.__LINE__,$logName);
    }else{
        $cs = $dialogflowData['custom_payload']['cs'];
        $parameter = $dialogflowData['parameters'];
        switch($cs){
            case 'welcome':
                $response = array('type' => 'text' , 'text'=>'嗨，你好~~~');
                break;
            case 'want_to_track_ones_expenses':
                $text = '請問你要記什麼帳?';
                // $response = array('type' => 'text' , 'text' => $text);
                $response = array('type' => 'event' , 'event' => 'ask_expenses' , 'text' => $text );
                break;
            case 'track_ones_expenses':

                //select 累積金額
                $sql_query = "SELECT account FROM account_user WHERE user_uuid = '$userID'";
                $db_result = DBSELECT($sql_query);
                
                if($db_result['result'] != true){
                    $response = array('type' => 'error' );
                    logMessage('sql error'.__LINE__,$logName);
                    break;
                }else{
                    $result = $db_result['statement']->fetch(PDO::FETCH_ASSOC);
                    $accumulation_account = $result['account'];
                }
                
                //取得dialogflow的金額
                if( !empty($parameter['number'])){
                    $current_account = $parameter['number'];
                    $account = $accumulation_account + $current_account;
                }elseif(!empty($parameter['unit-currency'])){
                    $current_account = $parameter['unit-currency']['amount'];
                    $account = $accumulation_account + $current_account;
                }else{
                    //無法取得數字
                    $response = array('type' => 'text' , 'text'=>'沒有辨識到您輸入的金額，請確認輸入的內容。');
                }

                $sql_query = "UPDATE account_user SET account='$account' WHERE user_uuid = '$userID'";
                $db_result = DBSELECT($sql_query);
                
                if($db_result['result'] != true){
                    $response = array('type' => 'error' );
                    logMessage('sql error'.__LINE__,$logName);
                    break;
                }


                if(!empty($parameter['food'])){
                    $text = '好，已記錄。目前累積'.$account.'元';
                    $response = array('type' => 'text' , 'text'=>$text);
                }elseif(!empty($parameter['drink'])){
                    $text = '好，已記錄。目前累積'.$account.'元';
                    $response = array('type' => 'text' , 'text'=>$text);
                }elseif(!empty($parameter['location'])){
                    $text = '好，已記錄。目前累積'.$account.'元';
                    $response = array('type' => 'text' , 'text'=>$text);
                }else{
                    $text = '好，已記錄。目前累積'.$account.'元';
                    $response = array('type' => 'text' , 'text'=>$text);
                }
                break;
            default:
                $response = array('type' => 'text' , 'text'=>'很抱歉，我不清楚你說的話。');
                break;
        }
    }
    logMessage('[HandleDialogflowResponse]'.json_encode($response,JSON_UNESCAPED_UNICODE),$logName);
    return $response;
}
function checkData($dialogflowData){
    $result['result'] = true;
    if(empty($dialogflowData['custom_payload'])){
        $result['result'] = false;
    }

    return $result;
}