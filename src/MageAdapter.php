<?php

namespace Pbogut\PadawanMagento;

use Entity\Project;

class MageAdapter
{
    const TYPE_HELPER = 'helpers';
    const TYPE_MODEL = 'models';
    const TYPE_RESURCE_MODEL = 'resource_models';

    private $project = null;

    public function setProject(Project $project)
    {
        $this->project = $project;
        return $this;
    }

    public function refresh()
    {
        echo "\n\n--" . __METHOD__ . "--\n\n";
    }

    public function getGroup($groupName)
    {
        if (!$this->project) {
            return array();
        }
        echo "\n\n--" . __METHOD__ . "--\n\n";
        return array();
    }

}
