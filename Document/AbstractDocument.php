<?php

namespace Sineflow\ElasticsearchBundle\Document;

/**
 * Document abstraction which introduces mandatory fields for the document.
 */
abstract class AbstractDocument implements DocumentInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $score;

    /**
     * @var string
     */
    private $parent;

    /**
     * @var string
     */
    private $ttl;

    /**
     * When document is cloned id is set to null.
     */
    public function __clone()
    {
        $this->setId(null);
    }

    /**
     * Sets document unique id.
     *
     * @param string $documentId
     *
     * @return $this
     */
    public function setId($documentId)
    {
        $this->id = $documentId;

        return $this;
    }

    /**
     * Returns document id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets document score.
     *
     * @param string $documentScore
     *
     * @return $this
     */
    public function setScore($documentScore)
    {
        $this->score = $documentScore;

        return $this;
    }

    /**
     * Gets document score.
     *
     * @return string
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Sets parent document id.
     *
     * @param string $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns parent document id.
     *
     * @return null|string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Checks if document has a parent.
     *
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * Sets time to live timestamp.
     *
     * @param string $ttl
     *
     * @return $this
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * Returns time to live value.
     *
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }
}
