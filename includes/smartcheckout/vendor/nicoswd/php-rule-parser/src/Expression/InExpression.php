<?php declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rule\Expression;

use nicoSWD\Rule\TokenStream\TokenCollection;
use nicoSWD\Rule\Parser\Exception\ParserException;

final class InExpression extends BaseExpression
{
    public function evaluate($leftValue, $rightValue): bool
    {
        if ($rightValue instanceof TokenCollection) {
            $rightValue = $rightValue->toArray();
        }

        if (!is_array($rightValue)) {
            throw new ParserException(sprintf(
                'Expected array, got "%s"',
                gettype($rightValue)
            ));
        }

        return in_array($leftValue, $rightValue, true);
    }
}
