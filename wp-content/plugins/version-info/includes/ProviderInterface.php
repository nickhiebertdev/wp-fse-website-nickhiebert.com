<?php


namespace GauchoPlugins\VersionInfo;

interface ProviderInterface {

    public function getName();

    public function getKey();

    public function isAvailable();

    /**
     * @return array<string, mixed>
     */
    public function collect();
}
