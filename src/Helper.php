<?php

namespace Pbogut\PadawanMagento;

/**
 * Class Helper
 * @author Pawel Bogut
 */
class Helper
{
    /**
     * Lookin through working nodes and trying to find StaticCall
     * @return PhpParser\Node\Expr\StaticCall|null
     */
    public function findStaticCallNode($workingNode)
    {
        if ($workingNode instanceof \PhpParser\Node\Expr\StaticCall) {
            return $workingNode;
        }
        if (!$workingNode instanceof \PhpParser\NodeAbstract) {
            return false;
        }

        $subNodes = $workingNode->getSubNodeNames();
        foreach ($subNodes as $nodeName) {
            if ($result = $this->findStaticCallNode($workingNode->{$nodeName})) {
                return $result;
            }
        }

        return null;
    }
}
