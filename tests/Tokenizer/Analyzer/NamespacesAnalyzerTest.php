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

namespace PhpCsFixer\Tests\Tokenizer\Analyzer;

use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author VeeWee <toonverwerft@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer
 */
final class NamespacesAnalyzerTest extends TestCase
{
    /**
     * @dataProvider provideNamespacesCases
     */
    public function testNamespaces(string $code, array $expected): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new NamespacesAnalyzer();

        static::assertSame(
            serialize($expected),
            serialize($analyzer->getDeclarations($tokens))
        );
    }

    public function provideNamespacesCases()
    {
        return [
            ['<?php // no namespaces', [
                new NamespaceAnalysis(
                    '',
                    '',
                    0,
                    0,
                    0,
                    1
                ),
            ]],
            ['<?php namespace Foo\Bar;', [
                new NamespaceAnalysis(
                    'Foo\Bar',
                    'Bar',
                    1,
                    6,
                    1,
                    6
                ),
            ]],
            ['<?php namespace Foo\Bar{}; namespace Foo\Baz {};', [
                new NamespaceAnalysis(
                    'Foo\Bar',
                    'Bar',
                    1,
                    6,
                    1,
                    7
                ),
                new NamespaceAnalysis(
                    'Foo\Baz',
                    'Baz',
                    10,
                    16,
                    10,
                    17
                ),
            ]],
        ];
    }

    /**
     * @param string $code
     * @param int    $index
     *
     * @dataProvider provideGetNamespaceAtCases
     */
    public function testGetNamespaceAt($code, $index, NamespaceAnalysis $expected): void
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new NamespacesAnalyzer();

        static::assertSame(
            serialize($expected),
            serialize($analyzer->getNamespaceAt($tokens, $index))
        );
    }

    public function provideGetNamespaceAtCases()
    {
        return [
            [
                '<?php // no namespaces',
                1,
                new NamespaceAnalysis(
                    '',
                    '',
                    0,
                    0,
                    0,
                    1
                ),
            ],
            [
                '<?php namespace Foo\Bar;',
                5,
                new NamespaceAnalysis(
                    'Foo\Bar',
                    'Bar',
                    1,
                    6,
                    1,
                    6
                ),
            ],
            [
                '<?php namespace Foo\Bar{}; namespace Foo\Baz {};',
                5,
                new NamespaceAnalysis(
                    'Foo\Bar',
                    'Bar',
                    1,
                    6,
                    1,
                    7
                ),
            ],
            [
                '<?php namespace Foo\Bar{}; namespace Foo\Baz {};',
                13,
                new NamespaceAnalysis(
                    'Foo\Baz',
                    'Baz',
                    10,
                    16,
                    10,
                    17
                ),
            ],
        ];
    }
}
