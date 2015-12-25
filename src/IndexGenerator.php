<?php

namespace Smeagol07\PadawanMagento;

class IndexGenerator
{
    public function handleAfterGenerationEvent($event)
    {
        $project = $event->getProject();
        try {
            // $data = Indexer::getInstance()->setProject($project)->getData(true);
            // $project->addPlugin('padawan-magento', $data);
        } catch (\Exception $e) {
            return;
        }
    }
}