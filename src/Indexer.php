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
    const TYPE_MODEL = 'models';
    const TYPE_RESURCE_MODEL = 'resource_models';

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
        $classMap = $this->getProject()->getIndex()->getClasses();
        if ($this->data === null || $refresh === true) {
            exec(sprintf('php %s/mage.php %s', dirname(__FILE__), $this->getProject()->getRootFolder()), $output);
            $options = json_decode(implode('', $output), true);

            if (!isset($options['success']) || !$options['success']) {
                return $this->data;
            }

            foreach([self::TYPE_HELPER, self::TYPE_RESURCE_MODEL, self::TYPE_MODEL] as $type) {
                $this->data[$type] = isset($this->data[$type]) ? $this->data[$type] : array();
                uksort($options[$type], function($a, $b) {
                    return ($a == $b) ? 0 : ($a > $b ? -1 : 1);
                });
                foreach ($options[$type] as $helperXmlKey => $namespace) {
                    foreach ($classMap as $className => $_) {
                        if (!$namespace) {
                            continue;
                        }
                        if (strpos($className, $namespace) === 0 && !$this->isClassInMaped($className)) {
                            $name = $this->getFactoryName($helperXmlKey, $namespace, $className);
                            $this->data[$type][$name] = $className;
                        }
                    }
                }
            }

        }
        return $this->data;
    }

    protected function isClassInMaped($className) {
        $isInMap = false;
        foreach ($this->data as $group => $classList) {
            if (in_array($className, $classList)) {
                $isInMap = true;
                break;
            }
        }

        return $isInMap;
    }

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new Indexer();
        }

        return self::$_instance;
    }

    protected function getFactoryName($helperXmlKey, $namespace, $className) {
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