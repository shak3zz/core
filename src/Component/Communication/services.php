<?php

declare(strict_types=1);

namespace Stu\Component\Communication;

use JBBCode\Parser;
use Psr\Container\ContainerInterface;
use Stu\Component\Communication\Kn\KnFactory;
use Stu\Component\Communication\Kn\KnFactoryInterface;
use Stu\Lib\KnBbCodeDefinitionSet;
use Stu\Orm\Repository\KnCommentRepositoryInterface;
use function DI\autowire;
use function DI\create;
use function DI\get;

return [
    'kn_bbcode_parser' => function (ContainerInterface $c): Parser {
        $parser = new Parser();
        $parser->addCodeDefinitionSet(new KnBbCodeDefinitionSet());
        return $parser;
    },
    KnFactoryInterface::class => create(KnFactory::class)->constructor(
        get('kn_bbcode_parser'),
        get(KnCommentRepositoryInterface::class)
    )
];