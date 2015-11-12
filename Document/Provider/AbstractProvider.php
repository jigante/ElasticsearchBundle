<?php

namespace Sineflow\ElasticsearchBundle\Document\Provider;

use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;

/**
 * Base document provider
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var DocumentMetadataCollector
     */
    protected $metadataCollector;

    /**
     * @var string The type the provider is for
     */
    private $documentClass;

    /**
     * @param string                    $documentClass     The type the provider is for
     * @param DocumentMetadataCollector $metadataCollector The metadata collector
     */
    public function __construct($documentClass, DocumentMetadataCollector $metadataCollector)
    {
        $this->metadataCollector = $metadataCollector;
        $this->documentClass = $documentClass;
    }

    /**
     * @return string
     */
    protected function getDocumentClass()
    {
        return $this->documentClass;
    }

    /**
     * @return DocumentMetadata The metadata for the ES type the provider is for
     */
    protected function getDocumentMetadata()
    {
        return $this->metadataCollector->getDocumentMetadata($this->documentClass);
    }

    /**
     * Returns a PHP Generator for iterating over the full dataset of source data that is to be inserted in ES
     * The returned data can be either a document entity or an array ready for direct sending to ES
     *
     * @return \Generator<DocumentInterface|array>
     */
    abstract public function getDocuments();

    /**
     * Build and return a document entity from the data source
     * The returned data can be either a document entity or an array ready for direct sending to ES
     *
     * @param int|string $id
     * @return DocumentInterface|array
     */
    abstract public function getDocument($id);
}
