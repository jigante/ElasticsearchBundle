<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Registers language provider for multi-language entities
 */
class RegisterLanguageProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = $container->findTaggedServiceIds('sfes.language_provider');
        if (empty($providers)) {
            return;
        }

        if (count($providers) > 1) {
            throw new \InvalidArgumentException(sprintf('You may not have more than one language provider defined, found [%s].', implode(', ', array_keys($providers))));
        }

        $providerServiceName = key($providers);
        $providerDefinition = $container->getDefinition($providerServiceName);

        // Make sure the class implements LanguageProviderInterface
        $providerClass = $providerDefinition->getClass();
        $reflectionClass = new \ReflectionClass($providerClass);
        if (!$reflectionClass->implementsInterface('Sineflow\ElasticsearchBundle\LanguageProvider\LanguageProviderInterface')) {
            throw new \InvalidArgumentException(sprintf('Language provider "%s" must implement LanguageProviderInterface.', $providerClass));
        }

        $documentParser = $container->getDefinition('sfes.document_parser');
        $documentParser->addMethodCall('setLanguageProvider', [$providerDefinition]);
    }
}
