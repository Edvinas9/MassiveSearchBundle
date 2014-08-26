<?php

namespace Unit\Search;

use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Argument;
use Massive\Bundle\SearchBundle\Search\SearchManager;

class SearchManagerTest extends ProphecyTestCase
{ 
    public function setUp()
    {
        $this->adapter = $this->prophesize('Massive\Bundle\SearchBundle\Search\AdapterInterface');
        $this->metadataFactory = $this->prophesize('Metadata\MetadataFactory');
        $this->metadata = $this->prophesize('Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata');
        $this->classHierachyMetadata = $this->prophesize('Metadata\ClassHierarchyMetadata');
        $this->classHierachyMetadata->getOutsideClassMetadata()->willReturn($this->metadata);
        $this->eventDispatcher = $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->searchManager = new SearchManager(
            $this->adapter->reveal(),
            $this->metadataFactory->reveal(),
            $this->eventDispatcher->reveal()
        );

        $this->product = new \Massive\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIndexNonObject()
    {
        $this->searchManager->index('asd');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage There is no search mappin
     */
    public function testIndexNoMetadata()
    {
        $this->metadataFactory
            ->getMetadataForClass('Massive\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product')
            ->willReturn(null);

        $this->searchManager->index($this->product);
    }

    public function testIndex()
    {
        $this->metadataFactory
            ->getMetadataForClass('Massive\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product')
            ->willReturn($this->classHierachyMetadata);
        $this->metadata->getIdField()->willReturn('id');
        $this->metadata->getUrlField()->willReturn('url');
        $this->metadata->getTitleField()->willReturn('title');
        $this->metadata->getDescriptionField()->willReturn('body');
        $this->metadata->getFieldMapping()->willReturn(array(
            'title' => array(
                'type' => 'string',
            ),
            'body' => array(
                'type' => 'string',
            )
        ));
        $this->metadata->getIndexName()->willReturn('product');

        $this->searchManager->index($this->product);
        $this->adapter->index(Argument::type('Massive\Bundle\SearchBundle\Search\Document'));
    }
}
