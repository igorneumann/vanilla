<?php

namespace Vanilla\Job;

use Garden\QueueInterop\AbstractJob;
use Garden\QueueInterop\VanillaContextInterface;
use Garden\QueueInterop\VanillaContextAwareInterface;
use Psr\Log\LogLevel;

class ReadConfigAndLocaleJob extends AbstractJob implements VanillaContextAwareInterface
{

    /**
     *
     * @var VanillaContextInterface
     */
    private $vanillaContext;


    public function setVanillaContext(VanillaContextInterface $context) {
        $this->vanillaContext = $context;
    }

    public function run() {

        $configKey = 'EnabledApplications';
        $localeKey = 'test_description';

        $this->log(LogLevel::NOTICE, "Sample config for key '{key}': {config}", [
            'key'       => $configKey,
            'config'    => json_encode($this->vanillaContext->getConfig()->get('EnabledApplications'),JSON_PRETTY_PRINT)
        ]);

        $this->log(LogLevel::NOTICE, "Sample locale for key '{key}': {locale}", [
            'key'       => $localeKey,
            'locale'    => $this->vanillaContext->getLocale()->get('test_description')
        ]);

    }

}