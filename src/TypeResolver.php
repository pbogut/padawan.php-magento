<?php

namespace Pbogut\PadawanMagento;

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
    /** @var MageAdapter */
    private $mageAdapter;
    private $parentType;

    public function __construct(
        UseParser $useParser,
        MageAdapter $mageAdapter
    ) {
        $this->useParser = $useParser;
        $this->mageAdapter = $mageAdapter;
    }

    public function handleParentTypeEvent(TypeResolveEvent $e)
    {
        $this->parentType = $e->getType();
    }


    public function handleTypeResolveEvent(TypeResolveEvent $e, Project $project)
    {
        /** @var \Entity\Chain\MethodCall */
        $chain = $e->getChain();
        $args = $chain->getArgs();
        if ($chain->getType() === 'method' && count($args) > 0) {
            switch ($chain->getName()) {
                case 'helper': //Mage::helper()
                    $this->handleType(MageAdapter::TYPE_HELPER, $e, $project);
                    break;
                case 'getSingleton': //Mage::getSingleton()
                    //no break;
                case 'getModel': //Mage::gerModel()
                    $this->handleType(MageAdapter::TYPE_MODEL, $e, $project);
                    break;
                break;
                case 'getResourceModel': //Mage::gerResourceModel()
                    $this->handleType(MageAdapter::TYPE_RESURCE_MODEL, $e, $project);
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

    protected function handleType($type, TypeResolveEvent $e, Project $project)
    {
        $chain = $e->getChain();

        $args = $chain->getArgs();
        $firstArg = array_pop($args);
        $firstArg = $firstArg->value;

        if (!$firstArg instanceof String_) {
            return; //no string so bye bye
        }

        $result = $this->mageAdapter->getGroup($type);

        $helperName = $firstArg->value;

        $fqcn = $this->useParser->parseFQCN(sprintf('%s', $result[$helperName]));
        $e->setType($fqcn);
    }
}
