<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler;

use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Nop;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Php\PhpCodePrinter;
use function strrpos;
use function substr;

class Generator
{

    public function generateMapperFile(string $mapperClassName, MapperCompiler $mapperCompiler): string
    {
        $pos = strrpos($mapperClassName, '\\');
        $namespaceName = $pos === false ? '' : substr($mapperClassName, 0, $pos);
        $shortClassName = $pos === false ? $mapperClassName : substr($mapperClassName, $pos + 1);

        $builder = new PhpCodeBuilder($namespaceName);
        $printer = new PhpCodePrinter();

        $mapperClass = $builder->mapperClass($shortClassName, $mapperCompiler)
            ->getNode();

        $namespace = $builder->namespace($namespaceName)
            ->addStmts($builder->getImports())
            ->addStmt($mapperClass)
            ->getNode();

        return $printer->prettyPrintFile([
            new Declare_([new DeclareDeclare('strict_types', $builder->val(1))]),
            new Nop(),
            $namespace,
        ]);
    }

}
