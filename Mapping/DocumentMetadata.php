<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Holds document metadata.
 */
class DocumentMetadata
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @var string
     */
    private $type;

    /**
     * Resolves metadata.
     *
     * @param array $metadata
     */
    public function __construct(array $metadata)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->type = key($metadata);
        $this->metadata = $resolver->resolve($metadata[$this->type]);
    }

    /**
     * Configures options resolver.
     *
     * @param OptionsResolver $optionsResolver
     */
    protected function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired(['properties', 'fields', 'propertiesMetadata', 'objects', 'repositoryClass', 'className', 'shortClassName']);
    }

    /**
     * Retrieves type mapping for the Elasticsearch client
     *
     * @return array
     */
    public function getClientMapping()
    {
        $mapping = array_filter(
            array_merge(
                ['properties' => $this->getProperties()],
                $this->getFields()
            ),
            function ($value) {
                // Remove all empty non-boolean values from the mapping array
                return (bool) $value || is_bool($value);
            }
        );

        return $mapping;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->metadata['properties'];
    }

    /**
     * @return array
     */
    public function getPropertiesMetadata()
    {
        return $this->metadata['propertiesMetadata'];
    }

    /**
     * @return array
     */
    public function getObjects()
    {
        return $this->metadata['objects'];
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->metadata['fields'];
    }

    /**
     * @return string|null
     */
    public function getRepositoryClass()
    {
        return $this->metadata['repositoryClass'];
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->metadata['className'];
    }

    /**
     * @return string Class name in short notation (e.g. AppBundle:Product)
     */
    public function getShortClassName()
    {
        return $this->metadata['shortClassName'];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
