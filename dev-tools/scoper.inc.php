<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\Finder\Finder;

require_once __DIR__.'/vendor/autoload.php';

$prefix = (function (): string {
    exec('git rev-parse --short HEAD', $output, $exitCode);

    if (0 !== $exitCode) {
        exit('Could not get Git commit.');
    }

    return "_PhpCsFixer_{$output[0]}";
})();

$exclude = (function (): array {
    $finder = Finder::create()->files()->name('*.php')->in([
        __DIR__.'/../vendor/symfony/polyfill-ctype',
        __DIR__.'/../vendor/symfony/polyfill-intl-grapheme',
        __DIR__.'/../vendor/symfony/polyfill-intl-normalizer',
        __DIR__.'/../vendor/symfony/polyfill-mbstring',
        __DIR__.'/../vendor/symfony/polyfill-php73',
        __DIR__.'/../vendor/symfony/polyfill-php80',
        __DIR__.'/../vendor/symfony/polyfill-php81',
    ]);

    return array_map(
        fn (SplFileInfo $f) => $f->getRealPath(),
        iterator_to_array($finder),
    );
})();

return [
    'prefix' => $prefix,
    'exclude-files' => $exclude,
    'exclude-namespaces' => [
        'Symfony\\Polyfill\\Ctype',
        'Symfony\\Polyfill\\Intl\\Grapheme',
        'Symfony\\Polyfill\\Intl\\Normalizer',
        'Symfony\\Polyfill\\Mbstring',
        'Symfony\\Polyfill\\Php73',
        'Symfony\\Polyfill\\Php80',
        'Symfony\\Polyfill\\Php81',
    ],
    'exclude-constants' => [
        'HHVM_VERSION_ID',
        '__PHP_CS_FIXER_RUNNING__',
    ],
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'expose-namespaces' => ['PhpCsFixer'],
];
