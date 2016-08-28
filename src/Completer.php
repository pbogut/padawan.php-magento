<?php

namespace Pbogut\PadawanMagento;

use Complete\Completer\CompleterInterface;
use Entity\Completion\Entry;
use Entity\Project;
use Entity\Completion\Context;

class Completer implements CompleterInterface
{
    /** @var MageAdapter */
    private $mageAdapter;
    /** @var Helper */
    private $helper;

    public function __construct(MageAdapter $mageAdapter, Helper $helper)
    {
        $this->mageAdapter = $mageAdapter;
        $this->helper = $helper;
    }

    public function getEntries(Project $project, Context $context)
    {
        list($type, $isThis, $types, $workingNode) = $context->getData();
        // @see Plugin::handleCompleteEvent
        $workingNode = $this->helper->findStaticCallNode($workingNode);
        $methodName = $workingNode->name;

        switch ($methodName) {
            case 'helper':
                return $this->handleType(MageAdapter::TYPE_HELPER);
            case 'getSingleton': //Mage::getSingleton()
                //no break;
            case 'getModel': //Mage::gerModel()
                return $this->handleModel();
            case 'getResourceModel': //Mage::gerResourceModel()
                return $this->handleType(MageAdapter::TYPE_RESURCE_MODEL);
            case 'getStoreConfig': //Mage::getStoreConfig()
                //no break;
            case 'getStoreConfigFlag': //Mage::getStoreConfigFlag()
                //not implemented yet!
                break;
        }

        return [];
    }

    protected function handleModel()
    {
        $group= $this->mageAdapter->getGroup(MageAdapter::TYPE_MODEL);
        $data = array_keys($group);
        $data = array_filter($data, function ($serviceName) use ($data) {
            //skip depricated nodes, type still will be resolved
            return (strpos($serviceName, '/mysql4') === false);
        });
        return array_map(function ($serviceName) use ($group) {
            return new Entry(
                sprintf('\'%s\'', $serviceName),
                $group[$serviceName],
                '',
                $serviceName
            );
        }, $data);

    }

    protected function handleType($type)
    {
        $group = $this->mageAdapter->getGroup($type);
        return array_map(function ($serviceName)  use ($group){
            return new Entry(
                sprintf('\'%s\'', $serviceName),
                $group[$serviceName],
                '',
                $serviceName
            );
        }, array_keys($group));
    }
}
