<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Php;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\PrettyPrinter\Standard;
use function count;

/**
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class PhpCodePrinter extends Standard
{

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options + ['shortArraySyntax' => true]);
    }

    /**
     * @param array<Node> $stmts
     */
    public function prettyPrintFile(array $stmts): string
    {
        $code = '<?php ' . $this->prettyPrint($stmts);
        $code = Strings::replace($code, '#[ ]++$#m', '');
        $code = Strings::replace($code, '#\{\n{2,}#', "{\n");
        $code = Strings::replace($code, '#\n{2,}\}#', "\n}");
        $code = Strings::replace($code, '#\n{3,}#', "\n\n");
        $code = Strings::replace($code, '#\}\n{2,}(\h++\})#', "}\n$1");
        return $code . "\n";
    }

    protected function pStmt_ClassConst(ClassConst $node): string
    {
        return parent::pStmt_ClassConst($node) . "\n";
    }

    protected function pStmt_ClassMethod(ClassMethod $node): string
    {
        return $this->pAttrGroups($node->attrGroups)
            . $this->pModifiers($node->flags)
            . 'function ' . ($node->byRef ? '&' : '') . $node->name
            . '(' . $this->pMaybeMultiline($node->params) . ')'
            . ($node->returnType !== null ? ': ' . $this->p($node->returnType) : '')
            . ($node->stmts !== null
                ? $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}'
                : ';');
    }

    protected function pStmt_Nop(Nop $node): string
    {
        return "\n";
    }

    /**
     * @param array<Node> $nodes
     */
    protected function pStmts(array $nodes, bool $indent = true): string
    {
        if ($indent) {
            $this->indent();
        }

        $result = '';

        foreach ($nodes as $node) {
            $comments = $node->getComments();

            if (count($comments) > 0) {
                $result .= "\n" . $this->nl . $this->pComments($comments);

                if ($node instanceof Nop) {
                    continue;
                }
            }

            $result .= $this->nl . $this->p($node);
        }

        if ($indent) {
            $this->outdent();
        }

        return $result;
    }

    protected function pStmt_If(If_ $node): string
    {
        return $this->nl . parent::pStmt_If($node) . "\n";
    }

    protected function pStmt_Foreach(Foreach_ $node): string
    {
        return $this->nl . parent::pStmt_Foreach($node) . "\n";
    }

    protected function pExpr_New(New_ $node): string
    {
        $argsFormatted = match (count($node->args)) {
            0 => '()',
            1 => '(' . $this->p($node->args[0]) . ')',
            default => '(' . $this->pCommaSeparatedMultiline($node->args, true) . "{$this->nl})",
        };

        if ($node->class instanceof Class_) {
            return 'new ' . $this->pClassCommon($node->class, $argsFormatted);
        }

        return 'new ' . $this->pNewVariable($node->class) . $argsFormatted;
    }

}
