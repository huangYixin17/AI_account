<?php

define('LOG_FOLDER',__DIR__.'/../debug_log/');

define('DIALOGFLOW_PROJECT','account-vpbe');
define('LINE_CHANNEL_ACCESS_TOKEN', 'nYVAPjNhQ6F4EY09qNYjutB2yy63om8YG2uftXzBgbgQ3Vkv7AwkDXXUFyRJvKwR7B6IJtwJ1Y29n7ciSsu4NxrRUzPFfff8iJ+XRYZQXxyFapk/5/Hys6aFs+9leNityuLoH7QMzhJUrdDwkmr9LwdB04t89/1O/w1cDnyilFU=');
define('LINE_CHANNEL_SECRET', 'e02c0f918de424cc53f554d45c7f05b2');

define('ERROR_TEXT','很抱歉，目前無法為您服務，請稍後....');
function logMessage($data, $tag = null){
    if($data == 'error'){
        $data = "error on ".basename(__FILE__).":".__LINE__;
    }
    $d = new DateTime();
    $datetime = $d->format('D M d H:i:s.u Y');
    $date = $d->format('Ymd');
    $client = $_SERVER['REMOTE_ADDR'].":".$_SERVER['REMOTE_PORT'];
    $pid = getmypid();
    $text = "[$datetime] [self:notice] [pid $pid:tid xxxx] [client $client] $data".PHP_EOL;

    //檢查資料夾是否存在
    if(is_dir(LOG_FOLDER.$tag) === false )
    {
        mkdir(LOG_FOLDER.$tag);
    }
    
    //寫檔
    $file = fopen(LOG_FOLDER.$tag."/$date",'a');
    fwrite($file,$text);
    fclose($file);
}