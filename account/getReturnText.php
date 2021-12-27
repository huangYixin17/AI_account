<?php 
require_once(__DIR__.'/../dialogflow/dialogflow_class.php');


function GetReturnText($datas,$userID){
    logMessage('[data]'.json_encode($datas,JSON_UNESCAPED_UNICODE),'getReturnText');
    $arr = array();
    $dialogflow = new dialogflow_class();

    if($datas['type'] == 'text'){
        array_push($arr,$datas['text']);
    }elseif($datas['type'] == 'event'){
        $event = $datas['event'];
        $dialogflow->detect_intent_texts_event(DIALOGFLOW_PROJECT,$event,$userID);
        array_push($arr,$datas['text']);
    }elseif($datas['type'] == 'many_results'){
        foreach ($datas['many_results'] as $many_result){
            if($many_result['type'] == 'text'){
                array_push($arr,$many_result['text']);
            }elseif($many_result['type'] == 'event'){
                $event = $many_result['event'];
                $dialogflow->detect_intent_texts_event(DIALOGFLOW_PROJECT,$event,$userID);
                array_push($arr,$many_result['text']);
            }
        }

    }else{
        array_push($arr,ERROR_TEXT);
    }
    
    return $arr;
}