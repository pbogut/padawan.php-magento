<?php

function start($path, $file = null)
{
    $mageFile = "{$path}/app/Mage.php";
    if (!file_exists($mageFile)) {
        echo (json_encode(array(
            'success' => false,
            'message' => "Wrong magento path. File {$mageFile} not found."
        )));
    } else {
        require $mageFile;
        Mage::app();
        session_start();

        if ($file === null) {
            $config = Mage::getConfig()->loadModulesConfiguration('config.xml');
        } else {
            $config = Mage::getConfig()->loadFile($file);
        }

        echo (json_encode(array(
            'success' => true,
            'models' => handleModels($config),
            'resource_models' => handleResources($config),
            'helpers' => handleHelpers($config),
        )));
    }
}

function getDirContents($dir, &$results = array())
{
    $files = scandir($dir);

    foreach ($files as $value) {
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if (!is_dir($path)) {
            $results[] = $path;
        } elseif ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

function arrayToString($array)
{
    $output = array();
    foreach ($array as $key => $row) {
        $output[] = "{$key}:{$row}";
    }
    return implode("\n", $output);
}

function handleModels($config)
{
    $resourceModels = array();

    foreach ($config->getNode()->xpath('global/models/*') as $element) {
        $name = $element->getName();
        $isResource = !!count($config->getNode()->xpath("global/models/*/resourceModel[.='{$name}']"));
        $namespace = (string) $element->class;
        if (!$isResource) {
            $resourceModels[$name] = $namespace;
        }
    }

    $resourceModels['core'] = 'Mage_Core_Model';
    unset($resourceModels['core_resource']);

    return $resourceModels;
}

function handleDeprecatedResources($config)
{
    $resourceModels = array();

    foreach ($config->getNode()->xpath('global/models/*[resourceModel]') as $element) {
        $namespace = (string) current(
            $config->getNode()->xpath("global/models/{$element->resourceModel}/class")
        );
        /* $depricated = (string) current( */
        /*     $config->getNode()->xpath("global/models/{$element->resourceModel}/class") */
        /* ); */
        if (!$namespace) {
            continue;
        }
        $name = $element->getName();

        $resourceModels[$name] = $namespace;
    }

    $resourceModels['core'] = 'Mage_Core_Model_Resource';

    return $resourceModels;
}

function handleResources($config)
{
    $resourceModels = array();

    foreach ($config->getNode()->xpath('global/models/*[resourceModel]') as $element) {
        $namespace = (string) current(
            $config->getNode()->xpath("global/models/{$element->resourceModel}/class")
        );
        if (!$namespace) {
            continue;
        }
        $name = $element->getName();

        $resourceModels[$name] = $namespace;
    }

    $resourceModels['core'] = 'Mage_Core_Model_Resource';

    return $resourceModels;
}

function handleHelpers($config)
{
    foreach ($config->getNode('global/helpers')->asArray() as $name => $data) {
        if (isset($data['class'])) {
            $helpers[$name] = $data['class'];
        }
    }
    $helpers['core'] = 'Mage_Core_Helper'; //for some reason its not in magento config files

    return $helpers;
}

// var_dump($argv, $argc);
$path = isset($argv[1]) ? $argv[1] : null;
$file = isset($argv[2]) ? $argv[2] : null;
if (!$path) {
    die(json_encode(array(
        'success' => false,
        'message' => "Usage: php {$argv[0]} <magento_path> [xml_file]"
    )));
}
start($path, $file);
