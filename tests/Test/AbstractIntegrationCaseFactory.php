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

namespace PhpCsFixer\Tests\Test;

use PhpCsFixer\RuleSet\RuleSet;
use Psl\Json;
use Psl\Type;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
abstract class AbstractIntegrationCaseFactory implements IntegrationCaseFactoryInterface
{
    public function create(SplFileInfo $file): IntegrationCase
    {
        try {
            if (!preg_match(
                '/^
                            --TEST--           \r?\n(?<title>          .*?)
                       \s   --RULESET--        \r?\n(?<ruleset>        .*?)
                    (?:\s   --CONFIG--         \r?\n(?<config>         .*?))?
                    (?:\s   --SETTINGS--       \r?\n(?<settings>       .*?))?
                    (?:\s   --REQUIREMENTS--   \r?\n(?<requirements>   .*?))?
                    (?:\s   --EXPECT--         \r?\n(?<expect>         .*?\r?\n*))?
                    (?:\s   --INPUT--          \r?\n(?<input>          .*))?
                $/sx',
                $file->getContents(),
                $match
            )) {
                throw new \InvalidArgumentException('File format is invalid.');
            }

            $match = array_merge([
                'config' => null,
                'settings' => null,
                'requirements' => null,
                'expect' => null,
                'input' => null,
            ], $match);

            return new IntegrationCase(
                $file->getRelativePathname(),
                $this->determineTitle($file, $match['title']),
                $this->determineSettings($file, $match['settings'] ?: null),
                $this->determineRequirements($file, $match['requirements'] ?: null),
                $this->determineConfig($file, $match['config'] ?: null),
                $this->determineRuleset($file, $match['ruleset']),
                $this->determineExpectedCode($file, $match['expect']),
                $this->determineInputCode($file, $match['input'])
            );
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                sprintf('%s Test file: "%s".', $e->getMessage(), $file->getPathname()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Parses the '--CONFIG--' block of a '.test' file.
     *
     * @return array{indent: string, lineEnding: string}
     */
    protected function determineConfig(SplFileInfo $file, ?string $config): array
    {
        $parsed = null !== $config ? Json\decode($config) : [];

        $parsed['indent'] ??= '    ';
        $parsed['lineEnding'] ??= "\n";

        return Type\shape([
            'indent' => Type\string(),
            'lineEnding' => Type\string(),
        ])->coerce($parsed);
    }

    /**
     * Parses the '--REQUIREMENTS--' block of a '.test' file and determines requirements.
     *
     * @return array{php: int, 'php<': int}
     */
    protected function determineRequirements(SplFileInfo $file, ?string $config): array
    {
        $parsed = null !== $config ? Json\decode($config) : [];

        $parsed['php'] ??= \PHP_VERSION_ID;
        $parsed['php<'] ??= \PHP_VERSION_ID + 100;

        return Type\shape([
            'php' => Type\int(),
            'php<' => Type\int(),
        ])->coerce($parsed);
    }

    /**
     * Parses the '--RULESET--' block of a '.test' file and determines what fixers should be used.
     */
    protected function determineRuleset(SplFileInfo $file, string $config): RuleSet
    {
        return new RuleSet(Json\decode($config));
    }

    /**
     * Parses the '--TEST--' block of a '.test' file and determines title.
     */
    protected function determineTitle(SplFileInfo $file, string $config): string
    {
        return $config;
    }

    /**
     * Parses the '--SETTINGS--' block of a '.test' file and determines settings.
     *
     * @return array{checkPriority: bool, deprecations: list<string>, isExplicitPriorityCheck: bool}
     */
    protected function determineSettings(SplFileInfo $file, ?string $config): array
    {
        $parsed = null !== $config ? Json\decode($config) : [];

        $parsed['checkPriority'] ??= true;
        $parsed['deprecations'] ??= [];

        $parsed = Type\shape([
            'checkPriority' => Type\bool(),
            'deprecations' => Type\vec(Type\string()),
        ])->coerce($parsed);

        $priority = \in_array('priority', explode(\DIRECTORY_SEPARATOR, $file->getRelativePathname()), true);

        return array_merge($parsed, ['isExplicitPriorityCheck' => $priority]);
    }

    protected function determineExpectedCode(SplFileInfo $file, ?string $code): string
    {
        $code = $this->determineCode($file, $code, '-out.php');

        if (null === $code) {
            throw new \InvalidArgumentException('Missing expected code.');
        }

        return $code;
    }

    protected function determineInputCode(SplFileInfo $file, ?string $code): ?string
    {
        return $this->determineCode($file, $code, '-in.php');
    }

    private function determineCode(SplFileInfo $file, ?string $code, string $suffix): ?string
    {
        if (null !== $code) {
            return $code;
        }

        $candidateFile = new SplFileInfo($file->getPathname().$suffix, '', '');
        if ($candidateFile->isFile()) {
            return $candidateFile->getContents();
        }

        return null;
    }
}
