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
                return $this->handleHelper();
            case 'getSingleton': //Mage::getSingleton()
                //no break;
            case 'getModel': //Mage::gerModel()
                //not implemented yet!
                break;
            case 'getResourceModel': //Mage::gerResourceModel()
                //not implemented yet!
                break;
            case 'getStoreConfig': //Mage::getStoreConfig()
                //no break;
            case 'getStoreConfigFlag': //Mage::getStoreConfigFlag()
                //not implemented yet!
                break;
        }

    }

    protected function handleHelper() {
        $result = Indexer::getInstance()->getGroup(Indexer::TYPE_HELPER);
        var_dump($result);

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