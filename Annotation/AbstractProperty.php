<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

use Sineflow\ElasticsearchBundle\Mapping\Caser;
use Sineflow\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Makes sure that annotations are well formatted.
 */
abstract class AbstractProperty implements DumperInterface
{
    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var string
     *
     * @Required
     */
    public $type;

    /**
     * @var string
     */
    public $index;

    /**
     * @var string
     */
    public $analyzer;

    /**
     * @var string
     */
    public $indexAnalyzer;

    /**
     * @var string
     */
    public $searchAnalyzer;

    /**
     * @var bool
     */
    public $includeInAll;

    /**
     * @var float
     */
    public $boost;

    /**
     * @var bool
     */
    public $payloads;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var array<\Sineflow\ElasticsearchBundle\Annotation\MultiField>
     */
    public $fields;

    /**
     * @var array
     */
    public $fieldData;

    /**
     * @var string Object name to map.
     */
    public $objectName;

    /**
     * Defines if related object will have one or multiple values.
     *
     * @var bool OneToOne or OneToMany.
     */
    public $multiple;

    /**
     * @var int
     */
    public $ignoreAbove;

    /**
     * @var bool
     */
    public $store;

    /**
     * @var string
     */
    public $indexName;

    /**
     * @var string
     */
    public $format;

    /**
     * @var array
     */
    public $raw;

    /**
     * {@inheritdoc}
     */
    public function dump(array $options = [])
    {
        $array = array_diff_key(
            array_filter(
                get_object_vars($this),
                function ($value) {
                    return $value || is_bool($value);
                }
            ),
            array_flip(['name', 'objectName', 'multiple'])
        );

        return array_combine(
            array_map(
                function ($key) {
                    return Caser::snake($key);
                },
                array_keys($array)
            ),
            array_values($array)
        );
    }
}
