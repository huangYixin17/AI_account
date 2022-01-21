<?php


function HandleDialogflowResponse($dialogflowData,$userID){
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
                }elseif(!empty($parameter['unit-currency'])){
                    $current_account = $parameter['unit-currency']['amount'];
                }else{
                    //無法取得數字
                    $response = array('type' => 'text' , 'text'=>'沒有辨識到您輸入的金額，請確認輸入的內容。');
                }

                //is new month?
                $sql_query = "SELECT current_month FROM account_user WHERE user_uuid = '$userID'";
                $db_result = DBSELECT($sql_query);

                if($db_result['result'] == false){
                    $response = array('type' => 'error' );
                    logMessage('sql error'.__LINE__,$logName);
                    break;
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
                            $response = array('type' => 'error' );
                            logMessage('sql error'.__LINE__,$logName);
                            break;
                        }else{
                            $account = $current_account;
                        }
                    }else{
                        $account = $accumulation_account + $current_account;
                    }

                    $sql_query = "UPDATE account_user SET account='$account' WHERE user_uuid = '$userID'";
                    $db_result = DBSELECT($sql_query);
                    
                    if($db_result['result'] != true){
                        $response = array('type' => 'error' );
                        logMessage('sql error'.__LINE__,$logName);
                        break;
                    }
                }

                

                //新增資料到record_account
                $sql_query = "INSERT INTO record_account (record_uuid,user_uuid,`date`,account)
                                VALUES (:record_uuid,:user_uuid,:date_,:account)";
                $record_uuid = uuid();
                $column = array(
                    ':record_uuid' => $record_uuid,
                    ':user_uuid' => $userID,
                    ':date_' => date('Y-m-d'),
                    ':account' => $current_account
                    
                );
                $db_result = DBINSERT($sql_query,$column);
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

function uuid(){
    mt_srand((double)microtime()*10000);
    $charid = strtoupper(md5(uniqid(rand(), true)));
    $uuid = substr($charid, 0, 8)
        .substr($charid, 8, 4)
        .substr($charid,12, 4)
        .substr($charid,16, 4)
        .substr($charid,20,12);
    return $uuid;
}
function checkData($dialogflowData){
    $result['result'] = true;
    if(empty($dialogflowData['custom_payload'])){
        $result['result'] = false;
    }

    return $result;
}