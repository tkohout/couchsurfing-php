<?php
require_once "api.php";

//Get conversations 1 day back
$api = new CSAPI("aaaaa", "xxxxx");
$response = $api->getConversations(new DateTime("-1 day"), 10);

foreach ($response->conversations as $conversation){
  echo "<img width='50' src='" . $conversation->withUser->avatarUrl . "'>" . $conversation->withUser->publicName . "</br></br>";  
}

