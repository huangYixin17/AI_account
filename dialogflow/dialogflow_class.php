<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Google\Cloud\Dialogflow\V2\InputAudioConfig;
use Google\Cloud\Dialogflow\V2\OutputAudioConfig;
use Google\Cloud\Dialogflow\V2\QueryParameters;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\EventInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use GPBMetadata\Google\Cloud\Dialogflow\V2\AudioConfig;

class dialogflow_class{
    /**
     * Returns the result of detect intent with texts as inputs.
     * Using the same `session_id` between requests allows continuation
     * of the conversation.
     * @param $projectId
     * @param $text
     * @param $sessionId
     * @param string $languageCode
     * @return array
     * @throws \Google\ApiCore\ApiException
     * @throws \Google\ApiCore\ValidationException
     * @throws \ErrorException
     */

     //將text傳給dialogflow辨識
    public function detect_intent_texts($projectId, $text, $sessionId, $languageCode = 'zh-TW')
    {
        // new session
        $sessionsClient = new SessionsClient();
        $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());
        //printf('Session path: %s' . PHP_EOL, $session);

        // create text input
        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        // get response and relevant info
        $response = $sessionsClient->detectIntent($session, $queryInput);
        
        $queryResult = $response->getQueryResult();
        
        $outputContext = $queryResult->getOutputcontexts();
        if($outputContext->offsetExists(0) && $outputContext->offsetGet(0)->getParameters() != null){
            $parameters_original = $outputContext->offsetGet(0)->getParameters()->getFields();

            //This can get origingal parameters
            $array = $this->parameter_handle($parameters_original);
        }
        else{
            $param = $queryResult->getParameters();
            $parameters = $param->getFields();
            // $parameters = [];
            //This can get parameters
            $array = $this->parameter_handle($parameters);
        }
        
        

        //$queryText = $queryResult->getQueryText();
        $intent = $queryResult->getIntent()->getDisplayName();
        //$confidence = $queryResult->getIntentDetectionConfidence();
        $fulfilmentText = $queryResult->getFulfillmentText();
        $fulfilmentMessages = $queryResult->getFulfillmentMessages();
        // $param = $queryResult->getParameters();
        // $parameters = $param->getFields();

        // //This can get parameters
        // $array = $this->parameter_handle($parameters);

        //This can get custom payload
        if($fulfilmentMessages->offsetExists(1)){
            $fulfilmentMessages = $fulfilmentMessages->offsetGet(1)->getPayload()->getFields();
        }elseif($fulfilmentMessages->offsetGet(0)->getPayload() != null) {
            $fulfilmentMessages = $fulfilmentMessages->offsetGet(0)->getPayload()->getFields();
        }else{
            $fulfilmentMessages = [];
        }
        $array2 = $this->parameter_handle($fulfilmentMessages);

        $result = array(
            'fulfilmentText'=>$fulfilmentText,
            'custom_payload'=>$array2,
            'parameters'=>$array,
            'intent'=>$intent
        );

        $sessionsClient->close();
        return $result;
    }
    public function detect_intent_texts_sentiment($projectId, $text, $sessionId, $languageCode = 'zh-TW')
    {
        // new session
        $sessionsClient = new SessionsClient();
        $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());
        printf('Session path: %s' . PHP_EOL, $session);

        // create text input
        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        $queryParameters = new QueryParameters();
        $queryParameters->setSentimentAnalysisRequestConfig(true);
        $optionalArgs = array('queryParams'=>$queryParameters);

        // get response and relevant info
        $response = $sessionsClient->detectIntent($session, $queryInput, $optionalArgs);
        $queryResult = $response->getQueryResult();

        $fulfilmentText = $queryResult->getFulfillmentText();

        $sessionsClient->close();
        return $fulfilmentText;
    }

    //接續dialogflow對話的方式是event
    public function detect_intent_texts_event($projectId, $name, $sessionId, $parameter = NULL, $languageCode = 'zh-TW')
    {
        // new session
        $sessionsClient = new SessionsClient();
        $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());
        //printf('Session path: %s' . PHP_EOL, $session);

        // create text input
        $eventInput = new EventInput();
        $eventInput->setName($name);
        $eventInput->setParameters($parameter);
        $eventInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setEvent($eventInput);

        // get response and relevant info
        $response = $sessionsClient->detectIntent($session, $queryInput);
        $queryResult = $response->getQueryResult();
        $fulfilmentText = $queryResult->getFulfillmentText();

        $sessionsClient->close();
        return $fulfilmentText;
    }
    public function detect_intent_texts_OutputAudio($projectId, $text, $sessionId, $languageCode = 'zh-TW')
    {
        // new session
        $sessionsClient = new SessionsClient();
        $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());
        printf('Session path: %s' . PHP_EOL, $session);

        // create text input
        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        $outputAudioConfig = new OutputAudioConfig();
        $outputAudioConfig->setAudioEncoding(1);
        $operation['outputAudioConfig'] = $outputAudioConfig;

        // get response and relevant info
        $response = $sessionsClient->detectIntent($session, $queryInput, $operation);
        $queryResult = $response->getQueryResult();
        $fulfilmentText = $queryResult->getFulfillmentText();
        $param = $queryResult->getParameters();
        $parameters = $param->getFields();
        $array = array();
        //This can get parameters
        foreach ($parameters as $name=>$obj){
            if($obj->getStringValue() != '') {
                $array[$name] = $obj->getStringValue();
            }elseif($obj->getNumberValue() != 0){
                $array[$name] = $obj->getNumberValue();
            }elseif($obj->getListValue() != null){
                $s = $obj->getListValue()->getValues();
                foreach ($s as $name2=>$obj2){
                    if($obj2->getStringValue() != '') {
                        $array[$name][$name2] = $obj2->getStringValue();
                    }elseif($obj2->getNumberValue() != 0) {
                        $array[$name][$name2] = $obj2->getNumberValue();
                    }
                }
            }else{
                $array[$name] = '';
            }
        }
        $outputAudio = base64_encode($response->getOutputAudio());
        $file = fopen('test.txt','w');
        fwrite($file,$outputAudio);
        fclose($file);

        $result = array($fulfilmentText,$array);

        $sessionsClient->close();
        return $result;
    }
    public function detect_intent_texts_InputAudio($projectId, $text, $sessionId, $languageCode = 'zh-TW')
    {
        // new session
        $sessionsClient = new SessionsClient();
        $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());
        printf('Session path: %s' . PHP_EOL, $session);

        // create audio input
        $audioConfig = new InputAudioConfig();
        $audioConfig->setAudioEncoding(3);
        $audioConfig->setLanguageCode($languageCode);
        $audioConfig->setsampleRateHertz(16000);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setAudioConfig($audioConfig);

        $operation['inputAudio'] = $text;

        // get response and relevant info
        $response = $sessionsClient->detectIntent($session, $queryInput, $operation);
        $queryResult = $response->getQueryResult();
        $fulfilmentText = $queryResult->getFulfillmentText();
        $param = $queryResult->getParameters();
        $parameters = $param->getFields();
        $array = array();
        //This can get parameters
        foreach ($parameters as $name=>$obj){
            if($obj->getStringValue() != '') {
                $array[$name] = $obj->getStringValue();
            }elseif($obj->getNumberValue() != 0){
                $array[$name] = $obj->getNumberValue();
            }elseif($obj->getListValue() != null){
                $s = $obj->getListValue()->getValues();
                foreach ($s as $name2=>$obj2){
                    if($obj2->getStringValue() != '') {
                        $array[$name][$name2] = $obj2->getStringValue();
                    }elseif($obj2->getNumberValue() != 0) {
                        $array[$name][$name2] = $obj2->getNumberValue();
                    }
                }
            }else{
                $array[$name] = '';
            }
        }

        $result = array($fulfilmentText,$array);

        $sessionsClient->close();
        return $result;
    }

    private function parameter_handle($a){
        $array = array();
        foreach ($a as $name=>$obj){
            //error_log($name.':'.$obj->getKind());
            switch($obj->getKind()){
                case 'bool_value':
                    $array[$name] = $obj->getBoolValue();
                    break;
                case 'string_value':
                    $array[$name] = $obj->getStringValue();
                    break;
                case 'number_value':
                    $array[$name] = $obj->getNumberValue();
                    break;
                case 'list_value':
                    $arr = $obj->getListValue()->getValues();
                    $array[$name] = $this->parameter_handle($arr);
                    if(empty($array[$name])){
                        $array[$name] = [];
                    }
                    break;
                case 'struct_value':
                    $arr = $obj->getStructValue()->getFields();
                    $array[$name] = $this->parameter_handle($arr);
                    if(empty($array[$name])){
                        $array[$name] = [];
                    }
                    break;
                default:
                    error_log('unknown type on detect_intent_texts_line,name:'.$name);
                    $array[$name] = '';
                    break;
            }
        }
        return $array;
    }
}

