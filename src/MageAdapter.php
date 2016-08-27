<?php

namespace Pbogut\PadawanMagento;

use Entity\Project;

class MageAdapter
{
    const TYPE_HELPER = 'helpers';
    const TYPE_MODEL = 'models';
    const TYPE_RESURCE_MODEL = 'resource_models';

    /** @var $project Project **/
    private $project = null;
    private $mageClassRequired = false;
    private $mageClassInitiated = false;

    private $data = array();

    public function setProject(Project $project)
    {
        $this->project = $project;
        return $this;
    }

    //@todo get rid of it
    public function refresh()
    {
        $this->rebuildData();
    }

    /**
     * Check for flag file, if creted reload Magento xml data
     * @return boolean
     */
    public function checkXmlUpdate()
    {
        $rootFolder = $this->project->getRootFolder();
        $flagFile = "{$rootFolder}/.padawan/magento_reload_xml";
        if (!file_exists($flagFile)) {
            return false;
        }

        $this->rebuildData();
        unlink($flagFile);

        return true;
    }

    public function getGroup($groupName)
    {
        $this->initMage();
        $this->checkXmlUpdate();
        return isset($this->data[$groupName]) ? $this->data[$groupName] : [];
    }

    protected function initMage()
    {
        if (!$this->mageClassInitiated) {
            $this->requireMageClass();
            \Mage::app();
            $this->rebuildData();

            $this->mageClassInitiated = true;
        }
    }

    protected function requireMageClass()
    {
        if (!$this->mageClassRequired) {
            $classMap = $this->project->getIndex()->getClasses();
            require_once (string) $classMap['Mage']->file;

            $this->mageClassRequired = true;
        }
    }

    protected function rebuildData()
    {
        $config = \Mage::getConfig()->loadModulesConfiguration('config.xml');

        $classMap = $this->project->getIndex()->getClasses();

        $options = [
            'models' => $this->handleModels($config),
            'resource_models' => $this->handleResources($config),
            'helpers' => $this->handleHelpers($config, $classMap),
        ];

        foreach ([self::TYPE_HELPER, self::TYPE_RESURCE_MODEL, self::TYPE_MODEL] as $type) {
            $this->data[$type] = isset($this->data[$type]) ? $this->data[$type] : array();
            //@todo that part needs to be optimized

            foreach ($classMap as $className => $_) {
                foreach ($options[$type] as $helperXmlKey => $namespace) {
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

        $this->sortData();

        $this->mageClassInitiaed = true;
    }

    protected function sortData()
    {
        foreach ([self::TYPE_HELPER, self::TYPE_RESURCE_MODEL, self::TYPE_MODEL] as $type) {
            uksort($this->data[$type], function ($a, $b) {
                $a = strtolower($a);
                $b = strtolower($b);
                return ($a == $b) ? 0 : ($a < $b ? -1 : 1);
            });
        }
    }

    protected function getFactoryName($helperXmlKey, $namespace, $className)
    {
        $name = $helperXmlKey;
        $result = array();
        $classEnding = substr($className, strlen($namespace) + 1);
        foreach (explode('_', $classEnding) as $part) {
            $result[] = lcfirst($part);
        }
        $subname = implode('_', $result);
        if ($subname !== 'data') { //default helper, without sub-name
            $name .= "/{$subname}";
        }

        return $name;
    }

    protected function isClassInMaped($className)
    {
        $isInMap = false;
        foreach ($this->data as $classList) {
            if (in_array($className, $classList)) {
                $isInMap = true;
                break;
            }
        }

        return $isInMap;
    }

    protected function handleModels($config)
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

    protected function handleDeprecatedResources($config)
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

    protected function handleResources($config)
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

    protected function handleHelpers($config, $classMap)
    {
        foreach ($config->getNode('global/helpers')->asArray() as $name => $data) {
            if (isset($data['class'])) {
                $helpers[$name] = $data['class'];
            }
        }

        //for some reason not all magento helpers are defined in xml's
        $classList  = array_keys($classMap);
        foreach ($classList as $className) {
            preg_match('/^(Mage_([A-Za-z0-9]+)_Helper)_.*$/', $className, $matches);
            if (count($matches) === 3) {
                $name = strtolower($matches[2]);
                $helpers[$name] = (string) $matches[1];
            }
        }

        return $helpers;
    }
}
