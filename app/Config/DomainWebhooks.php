<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuration for the reverse Hub -> domain calls used by file operations.
 *
 * The starter currently registers one domain app (`cms`), so this config does
 * not assume a catalog/event topology that the project does not have. The
 * integration remains opt-in: without both the URL and secret, the Hub keeps
 * its current local-only behavior.
 */
class DomainWebhooks extends BaseConfig
{
    public string $internalSecret = '';

    /**
     * @var array<string, string> domain code => base URL (without trailing slash)
     */
    public array $domains = [];

    public int $httpTimeoutSeconds = 3;

    public function __construct()
    {
        parent::__construct();

        $this->internalSecret = (string) (env('HUB_INTERNAL_SECRET') ?: '');

        $cmsUrl = (string) (env('CMS_DOMAIN_URL') ?: '');
        if ($cmsUrl !== '') {
            $this->domains = ['cms' => $cmsUrl];
        }

        $timeout = env('HUB_DOMAIN_TIMEOUT');
        if ($timeout !== null && $timeout !== false && $timeout !== '') {
            $this->httpTimeoutSeconds = (int) $timeout;
        }
    }
}
