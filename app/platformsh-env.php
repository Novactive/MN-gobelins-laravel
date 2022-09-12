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
    mapPlatformShPostgreDatabase('postgre', $config);
    mapPlatformShElasticSearch('elasticsearch', $config);
    mapAdminAppUrl($config);
}

function mapPlatformShPostgreDatabase(string $relationshipName, Config $config) : void
{
    if (!$config->hasRelationship($relationshipName)) {
        return;
    }

    $credentials = $config->credentials($relationshipName);

    setEnvVar('DB_CONNECTION', $credentials['scheme']);
    setEnvVar('DB_HOST', $credentials['host']);
    setEnvVar('DB_PORT', $credentials['port']);
    setEnvVar('DB_DATABASE', $credentials['username']);
    setEnvVar('DB_USERNAME', $credentials['username']);
    setEnvVar('DB_PASSWORD', $credentials['password']);
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

function mapAdminAppUrl(Config $config) : void
{
    // If the APP_URL is already set, leave it be.
    if (getenv('ADMIN_APP_URL')) {
        return;
    }

    // If not on Platform.sh, say in a local dev environment, simply
    // do nothing.  Users need to set the host pattern themselves
    // in a .env file.
    if (!$config->inRuntime()) {
        return;
    }

    // for test only

    setEnvVar('ADMIN_APP_URL', "https://admin.pr-3-3wpr5ty-pfgzak6qutkji.fr-3.platformsh.site");
}