<?php


require "init.php";

// download with URL
if (isset($_POST['name']) && isset($_POST['folder']) && isset($_POST['url'])) {
    $name = $_POST['name'];
    $folder = $_POST['folder'];
    $url = $_POST['url'];
    $proc = (empty($name) || empty($folder) || empty($url)) ? false : true;
    // if all needed parameters ar not empty
    if ($proc) {
        // check name in history
        if (!check_history($conf['history'], $name)) {
            // warn about dublicate
            $ret['msg'][] = "Name already exists in history!  ( DUBLICATED )";
            $name="DUBLICATED-".$name;
        }
        // get main options
        $speedLimit = $_POST['speed_limit'] ?? false;
        $continue = $_POST['continue'] ?? true;
        $http = $_POST['http'] ?? false;
        $user_agent = $_POST['user_agent'] ?? false;

        $path = $conf['files_root'].DIRECTORY_SEPARATOR .$folder;
        check_folder($path);
        $data = [
            "filename" => $path.DIRECTORY_SEPARATOR.$name,
            "folder" => $path,
            "url" => $url
        ];
        $file = new maestroerror\wgd($data);

        // set main options
        $file = ($speedLimit) ? $file->speedLimit($speedLimit) : $file;
        $file = ($continue == "true") ? $file->continueIfStopped() : $file;
        $file = ($http != "true") ? $file->secure() : $file;
        $file = ($user_agent == "true") ? $file->allowUserAgent()->userAgent($conf['user_agent']) : $file;

        // Download file
        $log = $conf['logs_root'].DIRECTORY_SEPARATOR .$folder.DIRECTORY_SEPARATOR.$name.".txt";
        check_folder($conf['logs_root'].DIRECTORY_SEPARATOR .$folder);
        $file->setLog($log)->silent()->run();
        
        // Add new download in history
        $date = date("F j, Y, g:i a");
        $openUrl = $conf['main_url'].$conf['files_uri']."/".$folder."/".$name;
        $Hitem[$name] = [
            "url" => $openUrl,
            "folder" => $folder,
            "type" => "file",
            "log" => $log,
            "start" => "Download Started at $date",
            "comments" => []
        ];
        add_history($conf['history'], $Hitem);

        // return success msg
        $ret['msg'][] = "Download successfully started";
        return_json($ret);

    } else {
        $ret['msg'][] = "name, folder, url are required parameters";
        
        return_json($ret);
    }
    
    
// download with .txt file
} elseif (isset($_POST['txt_folder']) && isset($_FILES['txt_file'])) {
    $file_name = $_FILES['txt_file']['name'];
    $folder = $_POST['txt_folder'];
    // move .txt into txts folder
    check_folder($conf['txts_root'].DIRECTORY_SEPARATOR.$folder);
    $filepath = $conf['txts_root'].DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$file_name;
    move_uploaded_file($_FILES['txt_file']['tmp_name'],$filepath);
    // Read .txt file
    $handle = fopen($filepath, "r");
    $fileInfo = [];
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $arr = explode("/", $line);
            $endfile = end($arr);
            $fileInfo[$endfile]['filename'] = $endfile;
            $fileInfo[$endfile]['source'] = $line;
            $fileInfo[$endfile]['url'] = $conf['main_url'].$conf['files_uri']."/".$folder."/".$endfile;
        }
        fclose($handle);
    }
    // save fileinfo
    $openUrl = $conf['main_url'].$conf['txt_uri']."/".$folder."/".$file_name;

    // check proccess
    $proc = (!file_exists($filepath) || empty($folder)) ? false : true;
    if ($proc) {
        if (!check_history($conf['history'], $file_name)) {
            $ret['msg'][] = "File already exists in history, check it again! ( DUBLICATED )";
            $file_name="DUBLICATED-".$file_name;
        }
        // get options
        $speedLimit = $_POST['speed_limit'] ?? false;
        $continue = $_POST['continue'] ?? true;
        $http = $_POST['http'] ?? false;
        $user_agent = $_POST['user_agent'] ?? false;

        $path = $conf['files_root'].DIRECTORY_SEPARATOR .$folder;
        check_folder($path);
        $data = [
            "filename" => $path.DIRECTORY_SEPARATOR.$file_name,
            "folder" => $path,
            "url" => ""
        ];
        // Use options
        $file = new maestroerror\wgd($data);
        $file = ($speedLimit) ? $file->speedLimit($speedLimit) : $file;
        $file = ($continue == "true") ? $file->continueIfStopped() : $file;
        $file = ($http != "true") ? $file->secure() : $file;
        $file = ($user_agent == "true") ? $file->allowUserAgent()->userAgent($conf['user_agent']) : $file;
        // start download
        $log = $conf['logs_root'].DIRECTORY_SEPARATOR .$folder.DIRECTORY_SEPARATOR.$file_name.".txt";
        check_folder($conf['logs_root'].DIRECTORY_SEPARATOR .$folder);
        $file->setLog($log)->silent()->multiple()->file($filepath)->run();
        
        // Add history
        $date = date("F j, Y, g:i a");
        $Hitem[$file_name] = [
            "url" => $openUrl,
            "type" => "txt",
            'files' => $fileInfo,
            "folder" => $folder,
            "log" => $log,
            "start" => "Download Started at $date",
            "comments" => []
        ];
        add_history($conf['history'], $Hitem);

        $ret['success'] = "Download successfully started";
        return_json($ret);
    } else {
        $ret['msg'][] = "TXT file and folder are required parameters";
        
        return_json($ret);
    }
}
