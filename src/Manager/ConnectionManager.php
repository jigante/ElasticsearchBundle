<?php

namespace Sineflow\ElasticsearchBundle\Manager;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Sineflow\ElasticsearchBundle\DTO\BulkQueryItem;
use Sineflow\ElasticsearchBundle\Event\Events;
use Sineflow\ElasticsearchBundle\Event\PostCommitEvent;
use Sineflow\ElasticsearchBundle\Exception\BulkRequestException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * This class interacts with elasticsearch using injected client.
 */
class ConnectionManager
{
    /**
     * @var string The unique connection manager name (the key from the index configuration)
     */
    protected $connectionName;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $connectionSettings;

    /**
     * @var BulkQueryItem[] Container for bulk queries.
     */
    protected $bulkQueries;

    /**
     * @var array Holder for consistency, refresh and replication parameters.
     */
    protected $bulkParams;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LoggerInterface
     */
    protected $tracer;

    /**
     * @var bool
     */
    protected $autocommit;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var bool
     */
    protected $kernelDebug;

    /**
     * @param string $connectionName     The unique connection name
     * @param array  $connectionSettings Settings array
     * @param bool   $kernelDebug        The kernel.debug value
     */
    public function __construct(string $connectionName, array $connectionSettings, bool $kernelDebug)
    {
        $this->connectionName = $connectionName;
        $this->connectionSettings = $connectionSettings;
        $this->bulkQueries = [];
        $this->bulkParams = [];
        $this->autocommit = false;
        $this->kernelDebug = $kernelDebug;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setTracer(LoggerInterface $tracer)
    {
        $this->tracer = $tracer;
    }

    public function getTracer(): ?LoggerInterface
    {
        return $this->tracer;
    }

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    public function getClient(): Client
    {
        if (!$this->client) {
            $clientBuilder = ClientBuilder::create();
            $clientBuilder->setHosts($this->connectionSettings['hosts']);
            if (isset($this->connectionSettings['ssl_verification'])) {
                $clientBuilder->setSSLVerification($this->connectionSettings['ssl_verification']);
            }
            if ($this->tracer && $this->kernelDebug) {
                $clientBuilder->setTracer($this->tracer);
            }
            if ($this->logger) {
                $clientBuilder->setLogger($this->logger);
            }
            $this->client = $clientBuilder->build();
        }

        return $this->client;
    }

    public function getConnectionSettings(): array
    {
        return $this->connectionSettings;
    }

    public function isAutocommit(): bool
    {
        return $this->autocommit;
    }

    public function setAutocommit(bool $autocommit)
    {
        // If the autocommit mode is being turned on, commit any pending bulk items
        if (!$this->autocommit && $autocommit) {
            $this->commit();
        }

        $this->autocommit = $autocommit;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Adds query to bulk queries container.
     *
     * @param string $operation  One of: index, update, delete, create.
     * @param string $index      Elasticsearch index name.
     * @param array  $query      Bulk item query (aka optional_source in the ES docs)
     * @param array  $metaParams Additional meta data params for the bulk item
     */
    public function addBulkOperation(string $operation, string $index, array $query, array $metaParams = [])
    {
        $this->bulkQueries[] = new BulkQueryItem($operation, $index, $query, $metaParams);
    }

    /**
     * Returns the number of bulk operations currently queued
     */
    public function getBulkOperationsCount(): int
    {
        return \count($this->bulkQueries);
    }

    /**
     * Optional setter to change bulk query params.
     *
     * @param array $params Possible keys:
     *                      ['consistency'] = (enum) Explicit write consistency setting for the operation.
     *                      ['refresh']     = (boolean) Refresh the index after performing the operation.
     *                      ['replication'] = (enum) Explicitly set the replication type.
     */
    public function setBulkParams(array $params)
    {
        $this->bulkParams = $params;
    }

    /**
     * Executes the accumulated bulk queries to the index.
     *
     * @param bool $forceRefresh Make new data available for searching immediately
     *                           If immediate availability of the data for searching is not crucial, it's better
     *                           to set this to false, to get better performance. In the latter case, data would be
     *                           normally available within 1 second
     *
     * @throws BulkRequestException
     */
    public function commit(bool $forceRefresh = true)
    {
        if (empty($this->bulkQueries)) {
            return;
        }

        $bulkRequest = $this->getBulkRequest();

        $response = $this->getClient()->bulk($bulkRequest);
        if ($forceRefresh) {
            $this->refresh();
        }

        $this->bulkQueries = [];

        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new PostCommitEvent($response, $this), Events::POST_COMMIT);
        }

        if ($response['errors']) {
            $errorCount = $this->logBulkRequestErrors($response['items']);
            $e = new BulkRequestException(\sprintf('Bulk request failed with %s error(s)', $errorCount));
            $e->setBulkResponseItems($response['items'], $bulkRequest);
            throw $e;
        }
    }

    /**
     * Get the current bulk request queued for commit
     */
    private function getBulkRequest(): array
    {
        // Go through each bulk query item
        $bulkRequest = [];
        $cachedAliasIndices = [];
        foreach ($this->bulkQueries as $bulkQueryItem) {
            if (isset($cachedAliasIndices[$bulkQueryItem->getIndex()])) {
                $indices = $cachedAliasIndices[$bulkQueryItem->getIndex()];
            } else {
                // Check whether the target index is actually an alias pointing to more than one index
                // in which case, two separate bulk query operation will be set for each physical index
                $indices = \array_keys($this->getClient()->indices()->getAlias(['index' => $bulkQueryItem->getIndex()]));
                $cachedAliasIndices[$bulkQueryItem->getIndex()] = $indices;
            }
            foreach ($indices as $index) {
                foreach ($bulkQueryItem->getLines($index) as $bulkQueryLine) {
                    $bulkRequest['body'][] = $bulkQueryLine;
                }
            }
        }

        $bulkRequest = \array_merge($bulkRequest, $this->bulkParams);

        return $bulkRequest;
    }

    /**
     * Logs errors from a bulk request and return their count
     *
     * @param array $responseItems bulk response items
     *
     * @return int The errors count
     */
    private function logBulkRequestErrors(array $responseItems): int
    {
        $errorsCount = 0;
        foreach ($responseItems as $responseItem) {
            // Get the first element of the response item (its key could be one of index/create/delete/update)
            $action = \key($responseItem);
            $actionResult = \reset($responseItem);

            // If there was an error on that item
            if (!empty($actionResult['error']) && $this->logger) {
                ++$errorsCount;
                $this->logger->error(\sprintf('Bulk %s item failed', $action), $actionResult);
            }
        }

        return $errorsCount;
    }

    /**
     * Refresh all indices, making any newly indexed documents immediately available for search
     */
    public function refresh()
    {
        // Use an index wildcard to avoid deprecation warnings about accessing system indices
        // Passing this 'index' argument should not be necessary in ES8
        $this->getClient()->indices()->refresh(['index' => '*,-.*']);
    }

    /**
     * Send flush call to index.
     *
     * Causes a Lucene commit to happen
     * In most cases refresh() should be used instead, as this is a very expensive operation
     */
    public function flush()
    {
        $this->getClient()->indices()->flush();
    }

    /**
     * Return all defined aliases in the ES cluster with all indices they point to
     *
     * @return array The ES aliases
     */
    public function getAliases(): array
    {
        $aliases = [];
        // Get all indices and their linked aliases (exc. dot-prefixed indices) and invert the results
        // Passing this 'index' argument should not be necessary in ES8
        $indices = $this->getClient()->indices()->getAlias(['index' => '*,-.*']);
        foreach ($indices as $index => $data) {
            foreach ($data['aliases'] as $alias => $aliasData) {
                $aliases[$alias][$index] = [];
            }
        }

        return $aliases;
    }

    /**
     * Check whether all of the specified indexes/aliases exist in the ES server
     *
     * NOTE: This is a workaround function to the native indices()->exists() function of the ES client
     * because the latter generates warnings in the log file when index/alias does not exist
     *
     * @see https://github.com/elasticsearch/elasticsearch-php/issues/163
     *
     * $params['index'] = (list) A comma-separated list of indices/aliases to check (Required)
     *
     * @param array $params Associative array of parameters
     *
     * @throws InvalidArgumentException
     */
    public function existsIndexOrAlias(array $params): bool
    {
        if (!isset($params['index'])) {
            throw new InvalidArgumentException('Required parameter "index" missing');
        }

        $indicesAndAliasesToCheck = \array_flip(\explode(',', $params['index']));

        // Get all available indices (exc. dot-prefixed indices) with their aliases
        // Passing this 'index' argument should not be necessary in ES8
        $allAliases = $this->getClient()->indices()->getAlias(['index' => '*,-.*']);
        foreach ($allAliases as $index => $data) {
            if (isset($indicesAndAliasesToCheck[$index])) {
                unset($indicesAndAliasesToCheck[$index]);
            }
            foreach ($data['aliases'] as $alias => $_nothing) {
                if (isset($indicesAndAliasesToCheck[$alias])) {
                    unset($indicesAndAliasesToCheck[$alias]);
                }
            }
            if (empty($indicesAndAliasesToCheck)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether any of the specified index aliases exists in the ES server
     *
     * NOTE: This is a workaround function to the native indices()->existsAlias() function of the ES client
     * because the latter generates warnings in the log file when alias does not exists
     * When this is fixed, we should revert back to using the ES client's function, not this one
     *
     * @see https://github.com/elasticsearch/elasticsearch-php/issues/163
     *
     * @param array $params
     *                      $params['name']               = (list) A comma-separated list of alias names to return (Required)
     *
     * @throws InvalidArgumentException
     */
    public function existsAlias(array $params): bool
    {
        if (!isset($params['name'])) {
            throw new InvalidArgumentException('Required parameter "name" missing');
        }

        $aliasesToCheck = \explode(',', $params['name']);

        // Get all available indices (exc. dot-prefixed indices) with their aliases
        // Passing this 'index' argument should not be necessary in ES8
        $allAliases = $this->getClient()->indices()->getAlias(['index' => '*,-.*']);
        foreach ($allAliases as $index => $data) {
            foreach ($aliasesToCheck as $aliasToCheck) {
                if (isset($data['aliases'][$aliasToCheck])) {
                    return true;
                }
            }
        }

        return false;
    }
}
