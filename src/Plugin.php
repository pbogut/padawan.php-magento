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
    /** @var Helper */
    private $helper;

    private $factoryMethods;
    private $containerNames;
    private $project;

    public function __construct(
        EventDispatcher $dispatcher,
        TypeResolver $resolver,
        Completer $completer,
        IndexGenerator $generator,
        MageAdapter $mageAdapter,
        Helper $helper
    ) {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->completer = $completer;
        $this->generator = $generator;
        $this->mageAdapter = $mageAdapter;
        $this->helper = $helper;
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
    }

    public function handleProjectLoadEvent($e)
    {
        //if not a magento project, then there is nothing to do
        if (!$this->isMagentoProject($e->project)) {
            return;
        }

        $this->project = $e->project;
        // $data = $this->project->getPlugin('padawan-magento');
        $this->mageAdapter->setProject($e->project);
    }

    public function handleTypeResolveEvent($e)
    {
        if (!$this->project) {
            return;
        }
        $index = $this->project->getIndex();
        if ($this->checkForContainerClass($this->resolver->getParentType(), $index)) {
            $this->resolver->handleTypeResolveEvent($e, $this->project);
        }
    }

    public function handleCompleteEvent($e)
    {
        if (!$this->project) {
            return;
        }
        $context = $e->context;
        if ($context->isMethodCall()) {
            list($type, $isThis, $types, $workingNode) = $context->getData();
            // This is kind a workaround, for some reason types are empty
            // and workingNode is not function call when assaigning variable
            // even if (in my opinon) it shoud be (at this event at least).
            // Eg. $model = Mage::getModel( // I would expect this event to be
            // aware that completion should be provided for getModel function,
            // instead its getting useless information about var assign.
            // Unless I'm missing something here...
            $workingNode = $this->helper->findStaticCallNode($workingNode);
            $fqcm = end($types);
            if ($this->checkForContainerClass($fqcm)
                && $this->shouldComplete($workingNode)
            ) {
                $e->completer = $this->completer;
            }
        }
    }

    protected function shouldComplete($workingNode)
    {
        return $workingNode instanceof \PhpParser\Node\Expr\StaticCall
            && (string) $workingNode->name
            && $this->checkFactoryMethod($workingNode->name)
            && ($className = end($workingNode->class->parts))
            && in_array($className, $this->containerNames);
    }

    protected function checkFactoryMethod($methodName)
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

    protected function checkForContainerClass($fqcn)
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
