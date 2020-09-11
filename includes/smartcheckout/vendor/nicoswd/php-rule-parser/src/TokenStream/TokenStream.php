<?php declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rule\TokenStream;

use ArrayIterator;
use Closure;
use nicoSWD\Rule\Parser\Exception\ParserException;
use nicoSWD\Rule\Grammar\CallableUserFunctionInterface;
use nicoSWD\Rule\TokenStream\Token\BaseToken;

class TokenStream extends ArrayIterator
{
    /** @var ArrayIterator */
    private $stack;
    /** @var AST */
    private $ast;

    public function __construct(ArrayIterator $stack, AST $ast)
    {
        $this->stack = $stack;
        $this->ast = $ast;
    }

    public function next(): void
    {
        $this->stack->next();
    }

    public function valid(): bool
    {
        return $this->stack->valid();
    }

    /** @throws ParserException */
    public function current(): BaseToken
    {
        return $this->getCurrentToken()->createNode($this);
    }

    public function key(): int
    {
        return $this->stack->key();
    }

    public function rewind(): void
    {
        $this->stack->rewind();
    }

    /** @return ArrayIterator<BaseToken> */
    public function getStack(): ArrayIterator
    {
        return $this->stack;
    }

    private function getCurrentToken(): BaseToken
    {
        return $this->stack->current();
    }

    /** @throws ParserException */
    public function getVariable(string $variableName): BaseToken
    {
        try {
            return $this->ast->getVariable($variableName);
        } catch (Exception\UndefinedVariableException $e) {
            throw ParserException::undefinedVariable($variableName, $this->getCurrentToken());
        }
    }

    /** @throws ParserException */
    public function getFunction(string $functionName): Closure
    {
        try {
            return $this->ast->getFunction($functionName);
        } catch (Exception\UndefinedFunctionException $e) {
            throw ParserException::undefinedFunction($functionName, $this->getCurrentToken());
        }
    }

    /** @throws ParserException */
    public function getMethod(string $methodName, BaseToken $token): CallableUserFunctionInterface
    {
        try {
            return $this->ast->getMethod($methodName, $token);
        } catch (Exception\UndefinedMethodException $e) {
            throw ParserException::undefinedMethod($methodName, $this->getCurrentToken());
        } catch (Exception\ForbiddenMethodException $e) {
            throw ParserException::forbiddenMethod($methodName, $this->getCurrentToken());
        }
    }
}
