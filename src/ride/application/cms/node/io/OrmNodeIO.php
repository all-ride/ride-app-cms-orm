<?php

namespace ride\application\cms\node\io;

use ride\application\orm\model\NodeModel;

use ride\library\cms\node\io\AbstractNodeIO;
use ride\library\cms\node\type\SiteNodeType;
use ride\library\cms\node\Node;
use ride\library\cms\node\TrashNode;

class OrmNodeIO extends AbstractNodeIO {

    public function __construct(NodeModel $model) {
        $this->model = $model;
        $this->entries = array();

        $this->trashName = '_trash';
    }

    /**
     * Reads all the sites from the data source
     * @return array Array with the id of the site as key and the SiteNode as
     * value
     */
    protected function readSites() {
        $sites = $this->model->findSites();
        $siteNodes = $this->convertToNodes($sites);

        $revisions = array();
        $sites = array();
        foreach ($siteNodes as $siteNode) {
            $revisions[$siteNode->getId()][] = $siteNode->getRevision();
            $sites[$siteNode->getId()] = $siteNode;
        }

        foreach ($revisions as $id => $siteRevisions) {
            $sites[$id]->setRevisions($siteRevisions);
        }

        return $sites;
    }

    /**
     * Reads all the nodes from the data source
     * @param string $siteId Id of the site
     * @param string $revision Name of the revision
     * @return array Array with the id of the node as key and the Node as value
     */
    protected function readNodes($siteId, $revision) {
        $sites = $this->getSites();
        $nodes = $this->model->findBySite($siteId, $revision);
        $nodes = $this->convertToNodes($nodes, $dateModified);

        // set the parent node instances and site revisions
        foreach ($nodes as $node) {
            if ($node->getType() === SiteNodeType::NAME) {
                $node->setRevisions($sites[$node->getId()]->getRevisions());
                $node->setDateModified($dateModified);
            }

            $parentId = $node->getParentNodeId();
            if (!$parentId) {
                // site node
                $node->setWidgetIdOffset($this->widgetIdOffset);

                continue;
            }

            if (isset($nodes[$parentId])) {
                $node->setParentNode($nodes[$parentId]);
            } else {
                $rootId = $node->getRootNodeId();
                if (isset($nodes[$rootId])) {
                    $node->setParentNode($nodes[$rootId]);
                }
            }
        }

        return $nodes;
    }

    /**
     * Writes the provided node to the data source
     * @param \ride\library\cms\node\Node $node Node to write
     * @return null
     */
    protected function writeNode(Node $node) {
        $entry = $this->getEntryForNode($node);
        if (!$entry) {
            $entry = $this->model->createEntry();
            $entry->setNodeId($node->getId());
            $entry->setNodeType($node->getType());
        }

        $entry->setValuesFromNode($node);

        $this->model->save($entry);

        $this->entries[$this->getFingerprintFromNode($node)] = $entry;
    }

    /**
     * Deletes the provided node to the data source
     * @param \ride\library\cms\node\Node $node Node to delete
     * @return null
     */
    protected function deleteNode(Node $node) {
        // fetch entry
        $entry = $this->getEntryForNode($node);
        if (!$entry) {
            return;
        }

        $revision = $entry->getRevision();

        if ($revision == $this->trashName) {
            // node is in trash, permantly remove
            $this->model->delete($entry);
        } else {
            // move node to trash
            $entry->setRevision($this->trashName);

            $this->model->save($entry);
        }

        $fingerprint = $this->getFingerprintFromNode($node->getRootNode());

        // update site date modified
        if (!isset($this->entries[$fingerprint])) {
            return;
        }

        $site = $this->entries[$fingerprint];
        $site->setDateModified(time());

        $this->model->save($site);
    }

    /**
     * Restores a node
     */
    protected function restoreTrashNode($siteId, $revision, TrashNode $trashNode, $newParent = null) {
    }

    public function publish(Node $node, $revision, $recursive) {

    }

    private function getEntryForNode(Node $node) {
        $entry = null;

        $fingerprint = $this->getFingerprintFromNode($node);
        if (isset($this->entries[$fingerprint])) {
            $entry = $this->entries[$fingerprint];
        } elseif ($node->getPath()) {
            $entry = $this->model->getByPath($node->getPath(), $node->getRevision());
        }

        return $entry;
    }

    private function convertToNodes(array $entries, &$dateModified = null) {
        $nodes = array();
        $dateModified = 0;

        foreach ($entries as $index => $entry) {
            $dateModified = max(0, $entry->getDateModified());

            $node = $this->convertToNode($entry);
            $nodes[$node->getId()] = $node;
        }

        return $nodes;
    }

    private function convertToNode($entry) {
        $fingerprint = $this->getFingerprintFromEntry($entry);

        if (isset($this->entryNodes[$fingerprint])) {
            return $this->entryNodes[$fingerprint];
        }

        $node = $this->nodeModel->createNode($entry->getNodeType());
        $node->setId($entry->getNodeId());
        $node->setRevision($entry->getRevision());
        $node->setParent($entry->getParent());
        $node->setOrderIndex($entry->getOrderIndex());

        foreach ($entry->getProperties() as $property) {
            $node->set($property->getKey(), $property->getValue(), $property->getInherit());
        }

        $this->entries[$fingerprint] = $entry;
        $this->entryNodes[$fingerprint] = $node;

        return $node;
    }

    private function getFingerprintFromEntry($entry) {
        $fingerprint = '';

        $parent = $entry->getParent();
        if ($parent) {
            $tokens = explode(Node::PATH_SEPARATOR, $parent);
            $fingerprint .= array_shift($tokens) . Node::PATH_SEPARATOR;
        }

        return $fingerprint . $entry->getRevision() . Node::PATH_SEPARATOR . $entry->getNodeId();
    }

    private function getFingerprintFromNode($node) {
        return $node->getRootNodeId() . Node::PATH_SEPARATOR . $node->getRevision() . Node::PATH_SEPARATOR . $node->getId();
    }

}
