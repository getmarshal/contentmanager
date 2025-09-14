<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Laminas\Validator\ValidatorPluginManager;
use Marshal\Util\Database\Validator\PropertyConfigValidator;
use Marshal\Util\Database\Validator\TypeConfigValidator;
use Psr\Container\ContainerInterface;

final class ContentManagerFactory
{
    public function __invoke(ContainerInterface $container): ContentManager
    {
        $typesConfig = $container->get("config")["schema"]["types"] ?? [];
        if (! \is_array($typesConfig)) {
            throw new \InvalidArgumentException(\sprintf("Invalid properties config type %s", \get_debug_type($typesConfig)));
        }

        $propertiesConfig = $container->get("config")["schema"]["properties"] ?? [];
        if (! \is_array($propertiesConfig)) {
            throw new \InvalidArgumentException(\sprintf("Invalid properties config type %s", \get_debug_type($propertiesConfig)));
        }

        $validatorPluginManager = $container->get(ValidatorPluginManager::class);
        if (! $validatorPluginManager instanceof ValidatorPluginManager) {
            throw new \InvalidArgumentException(\sprintf("Invalid %s", ValidatorPluginManager::class));
        }

        $typeValidator = $validatorPluginManager->get(TypeConfigValidator::class);
        if (! $typeValidator instanceof TypeConfigValidator) {
            throw new \InvalidArgumentException(\sprintf("Invalid %s", TypeConfigValidator::class));
        }

        $propertyValidator = $validatorPluginManager->get(PropertyConfigValidator::class);
        if (! $propertyValidator instanceof PropertyConfigValidator) {
            throw new \InvalidArgumentException(\sprintf("Invalid %s", PropertyConfigValidator::class));
        }

        return new ContentManager($typeValidator, $propertyValidator, $typesConfig, $propertiesConfig);
    }
}
