<?php

use BrekiTomasson\PhpCsFixer\Config\Exceptions\InvalidPhpVersion;
use BrekiTomasson\PhpCsFixer\Config\RuleSet\Php8;
use BrekiTomasson\PhpCsFixer\Config\Factory;

try {
    $config = Factory::fromRuleSet(new Php8());
} catch (InvalidPhpVersion $exception) {
    echo $exception->getMessage();
    exit(1);
}

$config->getFinder()
    ->in([__DIR__ . '/src'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return $config;
