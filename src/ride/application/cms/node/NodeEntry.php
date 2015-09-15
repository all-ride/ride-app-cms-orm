<?php

namespace ride\application\cms\node;

use ride\application\orm\entry\NodeEntry as OrmNodeEntry;
use ride\application\orm\entry\NodePropertyEntry;

use ride\library\cms\node\Node;

class NodeEntry extends OrmNodeEntry {

    /**
     * Get the materialized path of the node. The path is used for the parent
     * field of a node.
     * @return string
     */
    public function getPath() {
        $parent = $this->getParent();
        if (!$parent) {
        	return $this->getNodeId();
        }

        return $parent . Node::PATH_SEPARATOR . $this->getNodeId();
    }

    public function setValuesFromNode(Node $node) {
        $properties = array();
        $entryProperties = $this->getProperties();
        $nodeProperties = $node->getProperties();

        foreach ($nodeProperties as $nodeProperty) {
            $found = false;
            foreach ($entryProperties as $entryProperty) {
                if ($nodeProperty->getKey() !== $entryProperty->getKey()) {
                    continue;
                }

                $entryProperty->setValue($nodeProperty->getValue());

                $properties[] = $entryProperty;

                $found = true;

                break;
            }

            if ($found) {
                continue;
            }

            $entryProperty = new NodePropertyEntry();
            $entryProperty->setKey($nodeProperty->getKey());
            $entryProperty->setValue($nodeProperty->getValue());
            $entryProperty->setInherit($nodeProperty->getInherit());

            $properties[] = $entryProperty;
        }

        $this->setNodeId($node->getId());
        $this->setNodeType($node->getType());
        $this->setRevision($node->getRevision());
        $this->setParent($node->getParent());
        $this->setOrderIndex($node->getOrderIndex());
        $this->setProperties($properties);
    }

}
