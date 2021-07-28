<?php 
require 'src/wgd.php';

$conf = require('lib.php');
$origin = $conf['access_origin'];
$checkLogs = $conf['check_logs_amount'];
$maxComments = $conf['max_comments'];

header("Access-Control-Allow-Origin: $origin");

function return_json($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
}

function check_folder($path) {
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

function add_history($history, $item) {
    $json = json_decode(file_get_contents($history), true);
    $json = $item + $json;
    $json = json_encode($json);
    file_put_contents($history, $json);
}

function check_history($history, $name) {
    $json = json_decode(file_get_contents($history), true);
    if (isset($json[$name])) {
        return false;
    }
    return true;
}

function check_logs($history, $checkLogs, $maxComments) {
    $json = json_decode(file_get_contents($history), true);
    $i = $checkLogs;
    foreach ($json as $name => $item) {
        if ($i > 0 && isset($item['log'])) {
            if(!file_exists($item['log'])) { return false; }
            $file = file($item['log']);
            $line = count($file)-1;
            $lastLine = $file[$line-2] ." | ". $file[$line-1] ." | ". $file[$line];
            $date = date("F j, Y, g:i a");
            $comment = $lastLine;
            if (!array_search($comment, $json[$name]['comments'])) {
                $json[$name]['comments'][$date] = $comment;
            }
            if (count($json[$name]['comments']) > $maxComments) {
                array_shift($json[$name]['comments']);
            }
        }
        $i--;
    }
    $json = json_encode($json);
    file_put_contents($history, $json);
}




/** Init functions */
// checks latest logs and updates information in history
check_logs($conf['history'], $checkLogs, $maxComments);