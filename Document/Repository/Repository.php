<?php

namespace Sineflow\ElasticsearchBundle\Document\Repository;

use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use Sineflow\ElasticsearchBundle\Finder\Finder;
use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;

/**
 * Base entity repository class.
 */
class Repository
{
    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * The document class in short notation (e.g. AppBundle:Product)
     *
     * @var string
     */
    protected $documentClass;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * The type metadata
     *
     * @var DocumentMetadata
     */
    protected $metadata;

    /**
     * Constructor.
     *
     * @param IndexManager              $indexManager
     * @param string                    $documentClass
     * @param Finder                    $finder
     * @param DocumentMetadataCollector $metadataCollector
     */
    public function __construct(IndexManager $indexManager, $documentClass, Finder $finder, DocumentMetadataCollector $metadataCollector)
    {
        $this->indexManager = $indexManager;
        $this->documentClass = $documentClass;
        $this->finder = $finder;
        $this->documentMetadataCollector = $metadataCollector;

        if ($this->documentMetadataCollector->getDocumentClassIndex($documentClass) !== $indexManager->getManagerName()) {
            throw new \InvalidArgumentException(sprintf('Type "%s" is not managed by index "%s"', $documentClass, $indexManager->getManagerName()));
        }

        // Get the metadata of the document class managed by the repository
        $this->metadata = $this->documentMetadataCollector->getDocumentMetadata($documentClass);
    }

    /**
     * Returns elasticsearch manager used in the repository.
     *
     * @return IndexManager
     */
    public function getIndexManager()
    {
        return $this->indexManager;
    }

    /**
     * Returns a single document data by ID or null if document is not found.
     *
     * @param string $id         Document Id to find.
     * @param int    $resultType Result type returned.
     *
     * @return DocumentInterface|null
     */
    public function getById($id, $resultType = Finder::RESULTS_OBJECT)
    {
        return $this->finder->get($this->documentClass, $id, $resultType);
    }

    /**
     * Executes a search and return results
     *
     * @param array $searchBody              The body of the search request
     * @param int   $resultsType             Bitmask value determining how the results are returned
     * @param array $additionalRequestParams Additional params to pass to the ES client's search() method
     * @param int   $totalHits               The total hits of the query response
     * @return mixed
     */
    public function find(array $searchBody, $resultsType = Finder::RESULTS_OBJECT, array $additionalRequestParams = [], &$totalHits = null)
    {
        return $this->finder->find([$this->documentClass], $searchBody, $resultsType, $additionalRequestParams, $totalHits);
    }

    /**
     * Returns the number of records matching the given query
     *
     * @param array $searchBody
     * @param array $additionalRequestParams
     * @return int
     */
    public function count(array $searchBody = [], array $additionalRequestParams = [])
    {
        return $this->finder->count([$this->documentClass], $searchBody, $additionalRequestParams);
    }

    /**
     * Rebuilds the data of a document and adds it to a bulk request for the next commit.
     * Depending on the connection autocommit mode, the change may be committed right away.
     *
     * @param int $id
     */
    public function reindex($id)
    {
        $this->indexManager->reindex($this->documentClass, $id);
    }

    /**
     * Adds document removal to a bulk request for the next commit.
     * Depending on the connection autocommit mode, the removal may be committed right away.
     *
     * @param string $id Document ID to remove.
     *
     * @return array
     */
    public function delete($id)
    {
        $this->indexManager->delete($this->documentClass, $id);
    }

    /**
     * Adds a document update to a bulk request for the next commit.
     *
     * @param string $id     Document id to update.
     * @param array  $fields Fields array to update (ignored if script is specified).
     * @param string $script Groovy script to update fields.
     * @param array  $params Additional parameters to pass to the client.
     */
    public function update($id, array $fields = [], $script = null, array $params = [])
    {
        $this->indexManager->update($this->documentClass, $id, $fields, $script, $params);
    }

    /**
     * Adds document to a bulk request for the next commit.
     * Depending on the connection autocommit mode, the update may be committed right away.
     *
     * @param DocumentInterface $document The document entity to index in ES
     */
    public function persist(DocumentInterface $document)
    {
        $this->indexManager->persist($document);
    }

    /**
     * Adds a prepared document array to a bulk request for the next commit.
     * Depending on the connection autocommit mode, the update may be committed right away.
     *
     * @param array $documentArray The document to index in ES
     */
    public function persistRaw(array $documentArray)
    {
        $this->indexManager->persistRaw($this->documentClass, $documentArray);
    }
}
