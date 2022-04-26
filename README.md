# Joole Component: Containers

This component allows you to register containers with objects, "pulling" dependencies from other containers. The component is based on the [joole-containers](https://github.com/ZloyNick/joole-containers) library.

## Getting started

* Install this dependency via composer: <code>composer install zloynick/joole-components-container</code>

## Configuration

Add to components this in your joole.php configuration file:
<pre>
<code>
'components' => [
        ...,
        [
            'name' => 'containers',
            'class' => \joole\components\containers\ContainersComponent::class,
            // Containers and their configuration.
            'options' => [
                'main' => [
                    ...,
                    ['class' => \joole\reflector\Reflector::class,],
                    ...,
                ],
                // You also can use dependencies for object building with:
                'my_custom_container' => [
                    ...
                    [
                        'class' => '\YourClass',
                        // A "YourClass" object will be created using a Reflector object from another container 
                        // if there is a "Reflector" type parameter in the object constructor.
                        'depends' => [
                            [
                                'class' => \joole\reflector\Reflector::class,
                                'owner' => 'main',
                            ],
                        ]
                    ],
                ],
                // The component also accepts input parameters for objects. Each of the parameters 
                // is a data type and it is important to understand this.
                'events_container' => [
                    [
                        'class' => '\EventStack',
                        // EventStack::__construct(bool $cleanAfterExecution, array $allowedEvents)
                        'params' => [
                            'cleanAfterExecution' => true,
                            'allowedEvents' => [
                                '\CustomEvent1',
                                '\CustomEvent2',
                            ],
                        ],
                    ]
                ]
            ],
            'routes' => __DIR__.'/routes/',
        ],
        ...,
    ],
</code>
</pre>