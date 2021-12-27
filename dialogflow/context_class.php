<?php

use Google\Cloud\Dialogflow\V2\ContextsClient;
use Google\Cloud\Dialogflow\V2\Context;

class context_class{
    //查詢是否context存在，存在代表目前正在對話，不存在代表對話被清空。dialogflow在未使用後的20分鐘會自動清空對話
    public function context_exist($projectId, $sessionId)
    {
        // get contexts
        $contextsClient = new ContextsClient();
        $parent = $contextsClient->sessionName($projectId, $sessionId);
        $contexts = $contextsClient->listContexts($parent);

        $result = false;
        //printf('Contexts for session %s' . PHP_EOL, $parent);
        foreach ($contexts->iterateAllElements() as $context) {
            // print relevant info
            //printf('Context name: %s' . PHP_EOL, $context->getName());
            //printf('Lifespan count: %d' . PHP_EOL, $context->getLifespanCount());
            $result = true;
        }

        $contextsClient->close();
        return $result;

    }

    public function context_delete_all($projectId, $sessionId)
    {
        $contextsClient = new ContextsClient();

        $contextName = $contextsClient->sessionName($projectId, $sessionId);
        $contextsClient->deleteAllContexts($contextName);
        //printf('Context deleted: %s' . PHP_EOL, $contextName);

        $contextsClient->close();
    }

    //取得context
    public function context_array($projectId, $sessionId)
    {
        // get contexts
        $contextsClient = new ContextsClient();
        $parent = $contextsClient->sessionName($projectId, $sessionId);
        $contexts = $contextsClient->listContexts($parent);

        $arr = array();
        foreach ($contexts->iterateAllElements() as $context) {
            // print relevant info
            $arr2 = array("name"=>$context->getName(),"life_span_count"=>$context->getLifespanCount());
            array_push($arr,$arr2);
        }

        $contextsClient->close();
        return $arr;
    }

    public function get_context_detail($projectId, $sessionId, $contexts)
    {
        // get contexts
        $contextsClient = new ContextsClient();
        $parent = $contextsClient->contextName($projectId, $sessionId, $contexts);
        $context = $contextsClient->getContext($parent);

        $parameters_strct = $context->getParameters()->getFields();
        $parameters = $this->context_parameter_handle($parameters_strct);

        $arr = array("name"=>$context->getName(),"life_span_count"=>$context->getLifespanCount(),"parameters"=>$parameters);

        $contextsClient->close();
        return $arr;
    }

    public function context_create($projectId, $contextId, $sessionId, $lifespan = 1, $parameters = null)
    {
        $contextsClient = new ContextsClient();

        // prepare context
        $parent = $contextsClient->sessionName($projectId, $sessionId);
        $contextName = $contextsClient->contextName($projectId, $sessionId, $contextId);
        $context = new Context();
        $context->setName($contextName);
        $context->setLifespanCount($lifespan);
        if(!empty($parameters)){
            $context->setParameters($parameters);
        }

        // create context
        $contextsClient->createContext($parent, $context);

        $contextsClient->close();
    }

    private function context_parameter_handle($a){
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
                    $array[$name] = $this->context_parameter_handle($arr);
                    if(empty($array[$name])){
                        $array[$name] = [];
                    }
                    break;
                case 'struct_value':
                    $arr = $obj->getStructValue()->getFields();
                    $array[$name] = $this->context_parameter_handle($arr);
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