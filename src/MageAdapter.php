<?php

namespace Pbogut\PadawanMagento;

use Entity\Project;

class MageAdapter
{
    const TYPE_HELPER = 'helpers';
    const TYPE_MODEL = 'models';
    const TYPE_RESURCE_MODEL = 'resource_models';

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
    public function refresh() { }

    public function getGroup($groupName)
    {
        $this->initMage();

        return isset($this->data[$groupName]) ? $this->data[$groupName] : [];
    }

    protected function initMage()
    {
        if (!$this->mageClassInitiaed) {
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

    protected function rebuildData($file = null)
    {
        if ($file === null) {
            $config = \Mage::getConfig()->loadModulesConfiguration('config.xml');
        } else {
            $config = \Mage::getConfig()->loadFile($file);
        }

        $options = [
            'models' => $this->handleModels($config),
            'resource_models' => $this->handleResources($config),
            'helpers' => $this->handleHelpers($config),
        ];

        $classMap = $this->project->getIndex()->getClasses();

        foreach ([self::TYPE_HELPER, self::TYPE_RESURCE_MODEL, self::TYPE_MODEL] as $type) {
            $this->data[$type] = isset($this->data[$type]) ? $this->data[$type] : array();
            uksort($options[$type], function ($a, $b) {
                return ($a == $b) ? 0 : ($a < $b ? -1 : 1);
            });
            //@todo that part needs to be optimized
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

        $this->mageClassInitiaed = true;
    }

    protected function getFactoryName($helperXmlKey, $namespace, $className)
    {
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

    protected function handleHelpers($config)
    {
        foreach ($config->getNode('global/helpers')->asArray() as $name => $data) {
            if (isset($data['class'])) {
                $helpers[$name] = $data['class'];
            }
        }
        $helpers['core'] = 'Mage_Core_Helper'; //for some reason its not in magento config files

        return $helpers;
    }
}
