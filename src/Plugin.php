<?php

namespace Pbogut\PadawanMagento;

use Complete\Completer\CompleterFactory;
use Complete\Resolver\NodeTypeResolver;
use Entity\FQCN;
use Generator\IndexGenerator as Generator;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Plugin
{
    /** @var Completer */
    private $completer;
    /** @var TypeResolver */
    private $resolver;
    /** @var EventDispatcher */
    private $dispatcher;
    /** @var IndexGenerator */
    private $generator;
    /** @war MageAdapter */
    private $mageAdapter;

    private $factoryMethods;
    private $containerNames;
    private $project;

    public function __construct(
        EventDispatcher $dispatcher,
        TypeResolver $resolver,
        Completer $completer,
        IndexGenerator $generator,
        MageAdapter $mageAdapter
    ) {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->completer = $completer;
        $this->generator = $generator;
        $this->mageAdapter = $mageAdapter;
        $this->containerNames = [
            'Mage',
        ];
        $this->factoryMethods = [
            'helper',
            'getModel',
            'getSingleton',
            'getResourceModel',
            // 'getStoreConfig',
            // 'getStoreConfigFlag',
        ];
    }

    public function init()
    {
        $this->dispatcher->addListener(
            'project.load',
            [$this, 'handleProjectLoadEvent']
        );
    }

    public function handleProjectLoadEvent($e)
    {
        //if not a magento project, then there is nothing to do
        if (!$this->isMagentoProject($e->project)) {
            return;
        }
        $this->dispatcher->addListener(
            NodeTypeResolver::BLOCK_START,
            [$this->resolver, 'handleParentTypeEvent']
        );
        $this->dispatcher->addListener(
            NodeTypeResolver::BLOCK_END,
            [$this, 'handleTypeResolveEvent']
        );
        $this->dispatcher->addListener(
            CompleterFactory::CUSTOM_COMPLETER,
            [$this, 'handleCompleteEvent']
        );
        $this->dispatcher->addListener(
            Generator::BEFORE_GENERATION,
            [$this->generator, 'handleAfterGenerationEvent']
        );

        $this->project = $e->project;
        // $data = $this->project->getPlugin('padawan-magento');
        $this->mageAdapter->setProject($e->project);
    }

    public function handleTypeResolveEvent($e)
    {
        $index = $this->project->getIndex();
        if ($this->checkForContainerClass($this->resolver->getParentType(), $index)) {
            $this->resolver->handleTypeResolveEvent($e, $this->project);
        }
    }

    public function handleCompleteEvent($e)
    {
        $context = $e->context;
        if ($context->isMethodCall()) {
            list($type, $isThis, $types, $workingNode) = $context->getData();
            if (isset($workingNode->name) && $this->checkFactoryMmethod($workingNode->name) && $this->checkForContainerClass(array_pop($types), $e->project->getIndex())) {
                $e->completer = $this->completer;
            }
        }
    }

    protected function checkFactoryMmethod($methodName)
    {
        if (!$methodName) {
            return false;
        }
        if (!is_string($methodName)) {
            return false;
        }
        if (in_array($methodName, $this->factoryMethods)) {
            return true;
        }
        return false;
    }

    protected function checkForContainerClass($fqcn, $index)
    {
        if (!$fqcn instanceof FQCN) {
            return false;
        }
        if (in_array($fqcn->toString(), $this->containerNames)) {
            return true;
        }
        return false;
    }

    protected function isMagentoProject($project)
    {
        $classes = $project->getIndex()->getClasses();
        $classList = array_keys($classes);

        return in_array('Mage', $classList);
    }
}
