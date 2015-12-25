<?php

function start($path, $action) {
    $mageFile = "{$path}/app/Mage.php";
    if(!file_exists($mageFile)) {
        die("\nWrong magento path. File {$mageFile} not found.");
    }
    require $mageFile;
    Mage::app();
    session_start();

    switch ($action) {
        case 'resource_models':
            throw new Exception('Not implemented yet!');
        case 'models':
            throw new Exception('Not implemented yet!');
        case 'configs':
            throw new Exception('Not implemented yet!');
        case 'helpers':
            handleHelpers();
        break;
    }

}

function getDirContents($dir, &$results = array()){
    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(!is_dir($path)) {
            $results[] = $path;
        } else if($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

function handleHelpers() {
    $config = Mage::getConfig()->loadModulesConfiguration('config.xml');
    foreach ($config->getnode('global/helpers')->asArray() as $name => $data) {
        if (isset($data['class']))
        echo("{$name}:{$data['class']}\n");
    }
    echo("core:Mage_Core_Helper\n"); //for some reason its not in magento config files
}

// var_dump($argv, $argc);
$path = @$argv[1];
$action = @$argv[2];
if (!$path || !$action) {
    die("\nUsage: php {$argv[0]} <magento_path> <action>");
}
start($path, $action);