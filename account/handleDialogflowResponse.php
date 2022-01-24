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
                $sql_query = "INSERT INTO record_account (record_uuid,user_uuid,created_date,account)
                                VALUES (:record_uuid,:user_uuid,:created_date,:account)";
                $record_uuid = uuid();
                $column = array(
                    ':record_uuid' => $record_uuid,
                    ':user_uuid' => $userID,
                    ':account' => $current_account,
                    ':created_date' => date('Y-m-d H:i:s')
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
            case 'accumulation_account':
                //查詢累積金額
                if(!empty($parameter['date-time'])){
                    $start_date = $parameter['date-time']['startDate'];
                    $end_date = $parameter['date-time']['endDate'];
                    $start_date = substr($start_date,0,10);
                    $end_date = substr($end_date,0,10);
                    $month = substr($start_date,0,7);
                    $sql_query = "SELECT SUM(account) as total FROM record_account WHERE user_uuid='$userID' AND created_date > '$start_date' AND created_date < '$end_date'";
                    $sql_result = DBSELECT($sql_query);
                    if($sql_result['result'] == true){
                        $statement_result = $sql_result['statement']->fetch(PDO::FETCH_ASSOC);
                        $total = $statement_result['total'];
                        if(empty($total)){
                            $total = 0;
                        }
                        $text = '好，為您查詢'.$month.'的累積金額是'.$total.'元';
                        $response = array('type' => 'text' , 'text'=>$text);
                    }else{
                        $response = array('type' => 'text' , 'text'=>ERROR_TEXT);
                        break;
                    }
                }else{
                    $text = '不好意思，我不清楚您要查詢哪一個月份，請輸入詳細一點。';
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