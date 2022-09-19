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

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\DowngradePhp80\Rector as Php80;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/../src',
        __DIR__.'/../tests',
    ]);

    $rectorConfig->skip(['tests/Fixtures/*']);

    $rectorConfig->phpVersion(PhpVersion::PHP_74);

    $rectorConfig->rule(Php80\Catch_\DowngradeNonCapturingCatchesRector::class);
    $rectorConfig->rule(Php80\ClassMethod\DowngradeTrailingCommasInParamUseRector::class);
    $rectorConfig->rule(Php80\Class_\DowngradePropertyPromotionRector::class);
    $rectorConfig->rule(Php80\FunctionLike\DowngradeMixedTypeDeclarationRector::class);
    $rectorConfig->rule(Php80\FunctionLike\DowngradeUnionTypeDeclarationRector::class);
    $rectorConfig->rule(Php80\Property\DowngradeMixedTypeTypedPropertyRector::class);
    $rectorConfig->rule(Php80\Property\DowngradeUnionTypeTypedPropertyRector::class);
};
