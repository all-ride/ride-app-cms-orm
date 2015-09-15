<?php

namespace ride\application\orm\model;

use ride\library\cms\node\type\SiteNodeType;
use ride\library\cms\node\Node;
use ride\library\orm\model\GenericModel;

class NodeModel extends GenericModel {

    public function findSites() {
        $query = $this->createQuery();
        $query->addCondition('{nodeType} = %1%', SiteNodeType::NAME);

        return $query->query();
    }

    public function findBySite($siteId, $revision) {
        $query = $this->createQuery();
        $query->addCondition('{revision} = %1%', $revision);
        $query->addCondition('{nodeId} = %1% OR {parent} = %1% OR {parent} LIKE %2%', $siteId, $siteId . '-%');

        return $query->query();
    }

    public function getByPath($path, $revision) {
        $query = $this->createQuery();
        $query->addCondition('{revision} = %1%', $revision);
        if ($path) {
            $query->addCondition('{parent} = %1%', $path);
        } else {
            $query->addCondition('{parent} IS NULL');
        }

        return $query->queryFirst();
    }

}
