<?php

declare(strict_types=1);

namespace Platformsh\LaravelBridge;

use Platformsh\ConfigReader\Config;

mapPlatformShEnvironmentVariables();

/**
 * Map Platform.Sh environment variables to the values Laravel expects.
 *
 * This is wrapped up into a function to avoid executing code in the global
 * namespace.
 */
function mapPlatformShEnvironmentVariables() : void
{
    $config = new Config();

    if (!$config->inRuntime()) {
        return;
    }

    // Map services as feasible.
    mapPlatformShElasticSearch('elasticsearch', $config);
}

function mapPlatformShElasticSearch(string $relationshipName, Config $config) : void
{
    if (!$config->hasRelationship($relationshipName)) {
        return;
    }

    $credentials = $config->credentials($relationshipName);

    setEnvVar('ELASTIC_HOST', $credentials['host']);
    setEnvVar('ELASTIC_PORT', $credentials['port']);
    setEnvVar('ELASTIC_USER', $credentials['username']);
    setEnvVar('ELASTIC_PASS', $credentials['password']);
    setEnvVar('ELASTIC_SCHEME', $credentials['scheme']);
}
