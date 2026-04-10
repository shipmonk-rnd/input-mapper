<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Php;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\PrettyPrinter\Standard;
use function strlen;

/**
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class PhpCodePrinter extends Standard
{

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

    protected function pComments(array $comments): string
    {
        return $this->nl . parent::pComments($comments);
    }

    protected function pStmt_ClassConst(ClassConst $node): string
    {
        return $this->nl . parent::pStmt_ClassConst($node) . "\n";
    }

    protected function pStmt_If(If_ $node): string
    {
        return $this->nl . parent::pStmt_If($node) . "\n";
    }

    protected function pStmt_Foreach(Foreach_ $node): string
    {
        return $this->nl . parent::pStmt_Foreach($node) . "\n";
    }

    protected function pMaybeMultiline(
        array $nodes,
        bool $trailingComma = true,
    ): string
    {
        if (!$this->hasNodeWithComments($nodes)) {
            $singleLine = $this->pCommaSeparated($nodes);

            if (strlen($singleLine) <= 120) {
                return $singleLine;
            }
        }

        return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
    }

}
