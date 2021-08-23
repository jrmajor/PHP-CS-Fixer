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

namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NewWithBracesFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'All instances created with new keyword must be followed by braces.',
            [
                new CodeSample("<?php\n\n\$x = new X;\n\$y = new class {};\n"),
                new CodeSample(
                    "<?php\n\n\$y = new class() {};\n",
                    ['anonymous_class' => false]
                ),
                new CodeSample(
                    "<?php\n\n\$x = new X();\n",
                    ['named_class' => false]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_NEW);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        static $nextTokenKinds = null;

        if (null === $nextTokenKinds) {
            $nextTokenKinds = [
                '?',
                ';',
                ',',
                '(',
                ')',
                '[',
                ']',
                ':',
                '<',
                '>',
                '+',
                '-',
                '*',
                '/',
                '%',
                '&',
                '^',
                '|',
                [T_CLASS],
                [T_IS_SMALLER_OR_EQUAL],
                [T_IS_GREATER_OR_EQUAL],
                [T_IS_EQUAL],
                [T_IS_NOT_EQUAL],
                [T_IS_IDENTICAL],
                [T_IS_NOT_IDENTICAL],
                [T_CLOSE_TAG],
                [T_LOGICAL_AND],
                [T_LOGICAL_OR],
                [T_LOGICAL_XOR],
                [T_BOOLEAN_AND],
                [T_BOOLEAN_OR],
                [T_SL],
                [T_SR],
                [T_INSTANCEOF],
                [T_AS],
                [T_DOUBLE_ARROW],
                [T_POW],
                [T_SPACESHIP],
                [CT::T_ARRAY_SQUARE_BRACE_OPEN],
                [CT::T_ARRAY_SQUARE_BRACE_CLOSE],
                [CT::T_BRACE_CLASS_INSTANTIATION_OPEN],
                [CT::T_BRACE_CLASS_INSTANTIATION_CLOSE],
            ];
        }

        for ($index = $tokens->count() - 3; $index > 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_NEW)) {
                continue;
            }

            $nextIndex = $tokens->getNextTokenOfKind($index, $nextTokenKinds);
            $nextToken = $tokens[$nextIndex];

            // new anonymous class definition
            if ($nextToken->isGivenKind(T_CLASS)) {
                $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
                $nextToken = $tokens[$nextIndex];

                // add braces to anonymous class definition
                if ($this->configuration['anonymous_class'] && !$nextToken->equals('(')) {
                    $this->insertBracesAfter($tokens, $tokens->getPrevMeaningfulToken($nextIndex));

                    continue;
                }

                // remove braces from anonymous class definition
                if (!$this->configuration['anonymous_class'] && $nextToken->equals('(')) {
                    $openingIndex = $nextIndex;
                    $closingIndex = $tokens->getNextMeaningfulToken($openingIndex);

                    // constructor has arguments - braces can not be removed
                    if (null === $closingIndex || !$tokens[$closingIndex]->equals(')')) {
                        continue;
                    }

                    $tokens->clearAt($closingIndex);
                    $tokens->clearTokenAndMergeSurroundingWhitespace($openingIndex);
                }

                continue;
            }

            // entrance into array index syntax - need to look for exit
            while ($nextToken->equals('[') || $nextToken->isGivenKind(CT::T_ARRAY_INDEX_CURLY_BRACE_OPEN)) {
                $nextIndex = $tokens->findBlockEnd($tokens->detectBlockType($nextToken)['type'], $nextIndex) + 1;
                $nextToken = $tokens[$nextIndex];
            }

            // new statement has a gap in it - advance to the next token
            if ($nextToken->isWhitespace()) {
                $nextIndex = $tokens->getNextNonWhitespace($nextIndex);
                $nextToken = $tokens[$nextIndex];
            }

            // new statement without ()
            if ($this->configuration['named_class']) {
                if (!$nextToken->equals('(') && !$nextToken->isObjectOperator()) {
                    $this->insertBracesAfter($tokens, $tokens->getPrevMeaningfulToken($nextIndex));
                }

                continue;
            }

            if (!$tokens[$nextIndex]->equals('(')) {
                continue;
            }

            $openingIndex = $nextIndex;
            $closingIndex = $tokens->getNextMeaningfulToken($openingIndex);

            // constructor has arguments - braces can not be removed
            if (null === $closingIndex || !$tokens[$closingIndex]->equals(')')) {
                continue;
            }

            $tokens->clearAt($closingIndex);
            $tokens->clearTokenAndMergeSurroundingWhitespace($openingIndex);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('named_class', 'Whether named classes should be followed by braces.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
            (new FixerOptionBuilder('anonymous_class', 'Whether anonymous classes should be followed by braces.'))
                ->setAllowedTypes(['bool'])
                ->setDefault(true)
                ->getOption(),
        ]);
    }

    private function insertBracesAfter(Tokens $tokens, int $index): void
    {
        $tokens->insertAt(++$index, [new Token('('), new Token(')')]);
    }
}
