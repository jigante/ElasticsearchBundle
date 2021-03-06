<?php

namespace Sineflow\ElasticsearchBundle\Tests\Functional\Mapping;

use Sineflow\ElasticsearchBundle\Mapping\DocumentParser;
use Sineflow\ElasticsearchBundle\Tests\AbstractContainerAwareTestCase;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class DocumentParserTest
 */
class DocumentParserTest extends AbstractContainerAwareTestCase
{
    /**
     * @var DocumentParser
     */
    private $documentParser;

    public function setUp()
    {
        $reader = new AnnotationReader;
        $locator = $this->getContainer()->get('sfes.document_locator');
        $separator = $this->getContainer()->getParameter('sfes.mlproperty.language_separator');
        $this->documentParser = new DocumentParser($reader, $locator, $separator);
        $this->documentParser->setLanguageProvider($this->getContainer()->get('app.es.language_provider'));
    }

    public function testParseNonDocument()
    {

        $reflection = new \ReflectionClass('Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\ObjCategory');
        $res = $this->documentParser->parse($reflection, []);

        $this->assertEquals([], $res);
    }

    public function testParse()
    {
        $reflection = new \ReflectionClass('Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product');
        $indexAnalyzers = [
            'default_analyzer' =>
                [
                    'type' => 'standard',
                ],
                'en_analyzer' =>
                [
                    'type' => 'standard',
                ],
        ];

        $res = $this->documentParser->parse($reflection, $indexAnalyzers);

        $expected = [
            'type' => 'product',
            'properties' =>
                [
                    'title' =>
                        [
                            'fields' =>
                                [
                                    'raw' =>
                                        [
                                            'type' => 'string',
                                            'index' => 'not_analyzed',
                                        ],
                                        'title' =>
                                        [
                                            'type' => 'string',
                                        ],
                                ],
                                'type' => 'string',
                        ],
                        'description' =>
                        [
                            'type' => 'string',
                        ],
                        'category' =>
                        [
                            'type' => 'object',
                            'properties' =>
                                [
                                    'id' =>
                                        [
                                            'type' => 'integer',
                                        ],
                                        'title' =>
                                        [
                                            'index' => 'not_analyzed',
                                            'type' => 'string',
                                        ],
                                        'tags' =>
                                        [
                                            'type' => 'object',
                                            'properties' =>
                                                [
                                                    'tagname' =>
                                                        [
                                                            'type' => 'string',
                                                        ],
                                                ],
                                        ],
                                ],
                        ],
                        'related_categories' =>
                        [
                            'type' => 'object',
                            'properties' =>
                                [
                                    'id' =>
                                        [
                                            'type' => 'integer',
                                        ],
                                        'title' =>
                                        [
                                            'index' => 'not_analyzed',
                                            'type' => 'string',
                                        ],
                                        'tags' =>
                                        [
                                            'type' => 'object',
                                            'properties' =>
                                                [
                                                    'tagname' =>
                                                        [
                                                            'type' => 'string',
                                                        ],
                                                ],
                                        ],
                                ],
                        ],
                        'price' =>
                        [
                            'type' => 'float',
                        ],
                        'location' =>
                        [
                            'type' => 'geo_point',
                        ],
                        'limited' =>
                        [
                            'type' => 'boolean',
                        ],
                        'released' =>
                        [
                            'type' => 'date',
                        ],
                        'ml_info-en' =>
                        [
                            'analyzer' => 'en_analyzer',
                            'fields'   =>
                                [
                                    'ngram' =>
                                        [
                                            'type'     => 'string',
                                            'analyzer' => 'en_analyzer',
                                        ],
                                ],
                            'type' => 'string',
                        ],
                        'ml_info-fr' =>
                        [
                            'analyzer' => 'default_analyzer',
                            'fields'   =>
                                [
                                    'ngram' =>
                                        [
                                            'type'     => 'string',
                                            'analyzer' => 'default_analyzer',
                                        ],
                                ],
                            'type' => 'string',
                        ],
                        'ml_info-default' =>
                        [
                            'type' => 'string',
                            'index' => 'not_analyzed',
                        ],
                        'pieces_count' =>
                        [
                            'fields' =>
                                [
                                    'count' =>
                                        [
                                            'type' => 'token_count',
                                            'analyzer' => 'whitespace',
                                        ],
                                ],
                                'type' => 'string',
                        ],
                ],
                'fields' =>
                [
                    'dynamic' => 'strict',
                ],
                'propertiesMetadata' =>
                [
                    'title' =>
                        [
                            'propertyName' => 'title',
                            'type' => 'string',
                            'multilanguage' => null,
                            'propertyAccess' => 1,
                        ],
                        'description' =>
                        [
                            'propertyName' => 'description',
                            'type' => 'string',
                            'multilanguage' => null,
                            'propertyAccess' => 1,
                        ],
                        'category' =>
                        [
                            'propertyName' => 'category',
                            'type' => 'object',
                            'multilanguage' => null,
                            'multiple' => null,
                            'propertiesMetadata' =>
                                [
                                    'id' =>
                                        [
                                            'propertyName' => 'id',
                                            'type' => 'integer',
                                            'multilanguage' => null,
                                            'propertyAccess' => 1,
                                        ],
                                        'title' =>
                                        [
                                            'propertyName' => 'title',
                                            'type' => 'string',
                                            'multilanguage' => null,
                                            'propertyAccess' => 1,
                                        ],
                                        'tags' =>
                                        [
                                            'propertyName' => 'tags',
                                            'type' => 'object',
                                            'multilanguage' => null,
                                            'multiple' => true,
                                            'propertiesMetadata' =>
                                                [
                                                    'tagname' =>
                                                        [
                                                            'propertyName' => 'tagName',
                                                            'type' => 'string',
                                                            'multilanguage' => null,
                                                            'propertyAccess' => 1,
                                                        ],
                                                ],
                                                'className' => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\ObjTag',
                                                'propertyAccess' => 1,
                                        ],
                                ],
                                'className' => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\ObjCategory',
                                'propertyAccess' => 1,
                        ],
                        'related_categories' =>
                        [
                            'propertyName' => 'relatedCategories',
                            'type' => 'object',
                            'multilanguage' => null,
                            'multiple' => true,
                            'propertiesMetadata' =>
                                [
                                    'id' =>
                                        [
                                            'propertyName' => 'id',
                                            'type' => 'integer',
                                            'multilanguage' => null,
                                            'propertyAccess' => 1,
                                        ],
                                        'title' =>
                                        [
                                            'propertyName' => 'title',
                                            'type' => 'string',
                                            'multilanguage' => null,
                                            'propertyAccess' => 1,
                                        ],
                                        'tags' =>
                                        [
                                            'propertyName' => 'tags',
                                            'type' => 'object',
                                            'multilanguage' => null,
                                            'multiple' => true,
                                            'propertiesMetadata' =>
                                                [
                                                    'tagname' =>
                                                        [
                                                            'propertyName' => 'tagName',
                                                            'type' => 'string',
                                                            'multilanguage' => null,
                                                            'propertyAccess' => 1,
                                                        ],
                                                ],
                                                'className' => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\ObjTag',
                                                'propertyAccess' => 1,
                                        ],
                                ],
                                'className' => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\ObjCategory',
                                'propertyAccess' => 1,
                        ],
                        'price' =>
                        [
                            'propertyName' => 'price',
                            'type' => 'float',
                            'multilanguage' => null,
                            'propertyAccess' => 1,
                        ],
                        'location' =>
                        [
                            'propertyName' => 'location',
                            'type' => 'geo_point',
                            'multilanguage' => null,
                            'propertyAccess' => 1,
                        ],
                        'limited' =>
                        [
                            'propertyName' => 'limited',
                            'type' => 'boolean',
                            'multilanguage' => null,
                            'propertyAccess' => 1,
                        ],
                        'released' =>
                        [
                            'propertyName' => 'released',
                            'type' => 'date',
                            'multilanguage' => null,
                            'propertyAccess' => 1,
                        ],
                        'ml_info' =>
                        [
                            'propertyName' => 'mlInfo',
                            'type' => 'string',
                            'multilanguage' => true,
                            'propertyAccess' => 1,
                        ],
                        'pieces_count' =>
                        [
                            'propertyName' => 'tokenPiecesCount',
                            'type' => 'string',
                            'multilanguage' => null,
                            'propertyAccess' => 1,
                        ],
                        '_id' =>
                        [
                            'propertyName' => 'id',
                            'type' => 'string',
                            'propertyAccess' => 1,
                        ],
                        '_score' =>
                        [
                            'propertyName' => 'score',
                            'type' => 'float',
                            'propertyAccess' => 1,
                        ],
                ],
                'objects' =>
                [
                    0 => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\ObjTag',
                    1 => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\ObjCategory',
                ],
                'repositoryClass' => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\Repository\\ProductRepository',
                'className' => 'Sineflow\\ElasticsearchBundle\\Tests\\app\\fixture\\Acme\\BarBundle\\Document\\Product',
                'shortClassName' => 'AcmeBarBundle:Product',
        ];

        $this->assertEquals($expected, $res);
    }
}
