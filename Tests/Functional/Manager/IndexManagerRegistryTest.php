<?php

namespace Sineflow\ElasticsearchBundle\Tests\Functional\Manager;

use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Sineflow\ElasticsearchBundle\Tests\AbstractContainerAwareTestCase;
use Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;

/**
 * Class IndexManagerTest
 */
class IndexManagerRegistryTest extends AbstractContainerAwareTestCase
{
    public function testGet()
    {
        $registry = $this->getContainer()->get('sfes.index_manager_registry');

        $im = $registry->get('foo');
        $this->assertInstanceOf(IndexManager::class, $im);

        $this->setExpectedException('RuntimeException');
        $im = $registry->get('nonexisting');
    }

    public function testGetByEntity()
    {
        $registry = $this->getContainer()->get('sfes.index_manager_registry');

        $product = new Product();
        $im = $registry->getByEntity($product);
        $this->assertInstanceOf(IndexManager::class, $im);
        $this->assertEquals('bar', $im->getManagerName());
    }
}
