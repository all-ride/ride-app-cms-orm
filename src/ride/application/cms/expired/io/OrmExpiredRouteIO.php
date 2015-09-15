<?php

namespace ride\application\cms\expired\io;

use ride\application\orm\model\ExpiredRouteModel;

use ride\library\cms\expired\io\ExpiredRouteIO;

/**
 * ORM input/output implementation of the expired routes
 */
class OrmExpiredRouteIO implements ExpiredRouteIO {

    /**
     * Constructs a new route IO
     * @param \ride\application\orm\model\ExpiredRouteModel $model
     * @return null
     */
    public function __construct(ExpiredRouteModel $model) {
        $this->model = $model;
    }

    /**
     * Sets the expired routes to the data source
     * @param string $siteId Id of the site
     * @param array $routes Array with ExpiredRoute objects
     * @return null
     */
    public function setExpiredRoutes($siteId, array $routes) {
        $expiredRouteEntries = $this->model->getBySite($siteId);
        foreach ($expiredRouteEntries as $entry) {
            $found = false;

            foreach ($routes as $index => $route) {
                if ($route->getNode() == $entry->getNode() && $route->getLocale() == $entry->getLocale() && $route->getPath() == $entry->getPath() && $route->getBaseUrl() == $entry->getBaseUrl()) {
                    $found = true;

                    unset($routes[$index]);

                    break;
                }
            }

            if (!$found) {
                $this->model->delete($entry);
            }
        }

        foreach ($routes as $route) {
            $entry = $this->model->createEntry();
            $entry->setSite($diteId);
            $entry->setNode($route->getNode());
            $entry->setLocale($route->getLocale());
            $entry->setPath($route->getPath());
            $entry->setBaseUrl($route->getBaseUrl());

            $this->model->save($entry);
        }
    }

    /**
     * Gets the expired routes from the data source
     * @param string $siteId Id of the site
     * @return array Array with ExpiredRoute objects
     */
    public function getExpiredRoutes($siteId) {
        $expiredRoutes = array();

        $expiredRouteEntries = $this->model->getBySite($siteId);
        foreach ($expiredRouteEntries as $entry) {
            $expiredRoutes[] = new ExpiredRoute($entry->getNode(), $entry->getLocale(), $entry->getPath(), $entry->getBaseUrl());
        }

        return $expiredRoutes;
    }

}
