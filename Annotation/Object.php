<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

/**
 * Annotation to mark a class as an object during the parsing process.
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @deprecated to be removed in version 1.0 due to Object not being allowed class name since PHP7. Use DocObject instead
 */
final class Object
{
}
