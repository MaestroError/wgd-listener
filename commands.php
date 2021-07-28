<?php 
require "init.php";
$json = file_get_contents("data/commands.json");

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

print_r($json);