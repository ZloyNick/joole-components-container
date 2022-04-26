<?php

declare(strict_types=1);

namespace joole\components\container;

use joole\framework\Application;
use joole\framework\component\BaseComponent;
use joole\containers\Container;
use joole\containers\NotFoundException;
use joole\framework\data\types\ImmutableArray;
use joole\framework\exception\component\ComponentException;
use joole\framework\exception\config\ConfigurationException;
use RuntimeException;

use function array_keys;
use function class_exists;
use function is_array;

/**
 * The Containers component.
 */
final class ContainersComponent extends BaseComponent implements \ArrayAccess
{

    private array|ImmutableArray $containers;

    /**
     * @param array $options
     * @return void
     * @throws ConfigurationException
     * @throws NotFoundException
     * @throws \ErrorException
     * @throws \ReflectionException
     * @throws \joole\framework\data\container\NotFoundException
     */
    public function init(array $options): void
    {
        $containerNames = array_keys($options);

        // Preparing containers before registering
        foreach ($containerNames as $name) {
            $containerNames[$name] = new Container();
        }

        if (!class_exists('joole\reflector\Reflector')) {
            $registeredContainers = [];
        }else{
            $registeredContainers = new ImmutableArray();
            $reflector = new ('\joole\reflector\Reflector')();
            $reflectedContainers = $reflector->buildFromObject($registeredContainers);// Creating containers

            $reflectedContainers->getProperty('items')->setValue($containerNames);
        }

        foreach ($options as $containerName => $containerData) {
            /** @var Container $container */
            $container = $registeredContainers[$containerName];

            foreach ($containerData as $objectArray) {
                $params = $objectArray['params'] ?? [];

                if (isset($objectArray['depends'])) {
                    foreach ($objectArray['depends'] as $dependData) {
                        // Connect to expected container
                        if (is_array($dependData)) {
                            if (!isset($dependData['class'])) {
                                throw new ConfigurationException(
                                    'The "class" index of the container "' . $containerName . '" is not detected!'
                                );
                            }

                            if (!class_exists($class = $dependData['class'])) {
                                throw new RuntimeException('Class "' . $class . '" doesn\'t exists!');
                            }

                            $expectedClass = $class;

                            if (!isset($dependData['owner'])) {
                                throw new ConfigurationException(
                                    'The "owner" index of the container "' . $containerName . '" is not detected!'
                                );
                            }

                            $ownerOfExpectedClass = $dependData['owner'];
                            $source = $registeredContainers[$ownerOfExpectedClass];

                            if (!$source) {
                                throw new NotFoundException('Container ' . $ownerOfExpectedClass . ' not registered!');
                            }

                            if (!$source->has($expectedClass)) {
                                throw new NotFoundException('Container ' . $expectedClass . ' not registered!');
                            }

                            $params[$expectedClass] = $source->get($expectedClass);
                        } else {
                            if (!$container->has($dependData)) {
                                throw new NotFoundException('Object ' . $dependData . ' not registered at container ' . $containerName);
                            }
                        }
                    }
                }

                $container->register($objectArray['class'], $params);
            }
        }

        $this->containers = $registeredContainers;
    }

    public function run(Application $app): void
    {

    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->containers[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->containers[$offset];
    }

    /**
     * Containers aren't writable.
     *
     * @deprecated
     * @throws ComponentException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ComponentException('Can\'t rewrite container "'.$offset.'"');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new ComponentException('Can\'t remove container "'.$offset.'". Please, remove this container from configuration.');
    }
}