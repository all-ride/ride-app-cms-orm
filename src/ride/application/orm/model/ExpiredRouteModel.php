<?php

namespace ride\application\orm\model;

use ride\library\orm\model\GenericModel;

class ExpiredRouteModel extends GenericModel {

    public function getBySite($siteId) {
        $query = $this->createQuery();
        $query->addCondition('{site} = %1%', $siteId);

        return $query->query();
    }

    public function getByNode($siteId, $nodeId) {
        $query = $this->createQuery();
        $query->addCondition('{site} = %1% AND {node} = %2%', $siteId, $nodeId);

        return $query->query();
    }

}
