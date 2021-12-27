<?php


function HandleDialogflowResponse($dialogflowData){
    $checkDataResponse = checkData($dialogflowData);
    if($checkDataResponse['result'] != true){
        $response = array('type' => 'error');
    }else{
        $cs = $dialogflowData['custom_payload']['cs'];
        $parameter = $dialogflowData['parameter'];
        switch($cs){
            case 'welcome':
                $response = array('type' => 'text' , 'text'=>'嗨，你好~~~');
                break;
            case 'want_to_track_ones_expenses':
                $text = '請問你要記什麼帳?';
                $response = array('type' => 'text' , 'text' => $text);
                // $response = array('type' => 'many_results' , 'many_results' => array(['type'=>'text','text'=>'嘿嘿'],['type'=>'text','text'=>'uxxx']));
                break;
            case 'track_ones_expenses':
                if(!empty($parameter['food'])){

                }elseif(!empty($parameter['drink'])){

                }else{
                    $response = array('type' => 'text' , 'text'=>'很抱歉，我沒有辨識到您的記帳類別，請你選擇以下的類別記錄。','type_button');
                }
                break;
            default:
                $response = array('type' => 'text' , 'text'=>'很抱歉，我不清楚你說的話。');
                break;
        }
    }
    logMessage('[HandleDialogflowResponse]'.json_encode($response,JSON_UNESCAPED_UNICODE),'handleDialogflowResponse');
    return $response;
}
function checkData($dialogflowData){
    $result['result'] = true;
    if(empty($dialogflowData['custom_payload'])){
        $result['result'] = false;
    }

    return $result;
}