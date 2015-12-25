<?php

namespace Smeagol07\PadawanMagento;

use Complete\Completer\CompleterInterface;
use Entity\Completion\Entry;
use Entity\Project;
use Entity\Completion\Context;

class Completer implements CompleterInterface
{

    public function getEntries(Project $project, Context $context)
    {
        list($type, $isThis, $types, $workingNode) = $context->getData();
        $methodName = $workingNode->name;

        switch ($methodName) {
            case 'helper':
                return $this->handleType(Indexer::TYPE_HELPER);
            case 'getSingleton': //Mage::getSingleton()
                //no break;
            case 'getModel': //Mage::gerModel()
                return $this->handleModel();
            case 'getResourceModel': //Mage::gerResourceModel()
                return $this->handleType(Indexer::TYPE_RESURCE_MODEL);
            case 'getStoreConfig': //Mage::getStoreConfig()
                //no break;
            case 'getStoreConfigFlag': //Mage::getStoreConfigFlag()
                //not implemented yet!
                break;
        }

    }

    protected function handleModel() {
        $result = Indexer::getInstance()->getGroup(Indexer::TYPE_MODEL);
        $result = array_keys($result);
        $result = array_filter($result, function ($serviceName) use ($result) {
            //skip depricated nodes, type still will be resolved
            return (strpos($serviceName, '/mysql4') === false);
        });

        return array_map(function ($serviceName) {
            return new Entry(
                sprintf('\'%s\'', $serviceName),
                '',
                '',
                $serviceName
            );
        }, $result);

    }

    protected function handleType($type) {
        $result = Indexer::getInstance()->getGroup(Indexer::TYPE_RESURCE_MODEL);

        return array_map(function ($serviceName) {
            return new Entry(
                sprintf('\'%s\'', $serviceName),
                '',
                '',
                $serviceName
            );
        }, array_keys($result));
    }


}