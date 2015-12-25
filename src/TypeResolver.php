<?php

namespace Smeagol07\PadawanMagento;

use Parser\UseParser;
use Complete\Resolver\TypeResolveEvent;
use Entity\FQCN;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use Entity\Project;

class TypeResolver
{
    /** @var UseParser */
    private $useParser;
    private $parentType;

    public function __construct(
        UseParser $useParser
    ) {
        $this->useParser = $useParser;
    }

    public function handleParentTypeEvent(TypeResolveEvent $e)
    {
        $this->parentType = $e->getType();
    }


    public function handleTypeResolveEvent(TypeResolveEvent $e, Project $project)
    {
        /** @var \Entity\Chain\MethodCall */
        $chain = $e->getChain();
        //tiny hackish solution to reindex Magento XML files
        if ($chain->getType() === 'method' && $chain->getName() == 'padawan_refresh') {
            Indexer::getInstance()->refresh();
        }
        if ($chain->getType() === 'method' && count($chain->getArgs()) > 0) {
            switch ($chain->getName()) {
                case 'helper': //Mage::helper()
                    $this->handleHelper($e, $project);
                    break;
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
    }

    public function getParentType()
    {
        return $this->parentType;
    }

    protected function handleHelper(TypeResolveEvent $e, Project $project) {
        $chain = $e->getChain();

        $firstArg = array_pop($chain->getArgs())->value;

        if (!$firstArg instanceof String_) {
            break; //no string so bye bye
        }

        $result = Indexer::getInstance()->getGroup(Indexer::TYPE_HELPER);

        $helperName = $firstArg->value;

        $fqcn = $this->useParser->parseFQCN(sprintf('%s', $result[$helperName]));
        $e->setType($fqcn);
    }
}
