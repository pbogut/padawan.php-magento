<?php

namespace Smeagol07\PadawanMagento;

/**
 * I'm parsing Magento XML Files to generate completions
 * and to resolve types
 *
 * I'm here because generate Padawan indexing for Magento project takes ages
 * and there is no simple way to monitor XML file changes.
 * As it is very common to modify XML files in Magento, I can reindex them
 * in no time (ok it, can take few seconds), just for completion and type
 * resolve purpose.
 */
class Indexer {

    const TYPE_HELPER = 'helpers';

    protected static $_instance = null;

    protected $data = null;
    protected $project = null;

    public function setProject($project) {
        $this->project = $project;
        return $this;
    }

    public function getProject() {
        return $this->project;
    }

    public function setData($data) {
        if (empty($data)) {
            $data = null;
        }
        $this->data = $data;
        return $this;
    }

    public function getGroup($name) {
        $data = $this->getData();
        if (isset($data[$name])) {
            return $data[$name];
        }

        return array();
    }

    public function refresh() {
        $this->getData(true);
    }

    public function getData($refresh = false) {
        $classMap = $this->getProject()->getIndex()->getClassMap();
        if ($this->data === null || $refresh === true) {
            exec(sprintf('php %s/mage.php %s helpers', dirname(__FILE__), $this->getProject()->getRootFolder()), $output);
            $result = array();
            foreach ($output as $line) {
                if(!$line) {
                    break;
                }

                list($helperXmlKey, $namespace) = explode(':', $line, 2);

                foreach ($classMap as $className => $_) {
                    if (strpos($className, $namespace) === 0) { //if more helpers in that namespace
                        $name = $this->getHelperName($helperXmlKey, $namespace, $className);
                        $result[$name] = $className;
                    }
                }
            }
            $this->data[self::TYPE_HELPER] = $result;
        }
        return $this->data;
    }

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new Indexer();
        }

        return self::$_instance;
    }

    protected function getHelperName($helperXmlKey, $namespace, $className) {
        $name = $helperXmlKey;
        $result = array();
        $classEnding = substr($className, strlen($namespace)+1);
        foreach (explode('_', $classEnding) as $part) {
            $result[] = lcfirst($part);
        }
        $subname = implode('_', $result);
        if ($subname !== 'data') { //default helper, without sub-name
            $name .= "/{$subname}";
        }

        return $name;
    }

    protected function __construct() {}

}