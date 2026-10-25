<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Commands;

use Blackcube\Mcp\Services\ApiKeyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Console command purging the expired and revoked MCP keys.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
#[AsCommand(
    name: 'mcp:keyGc',
    description: 'Purge expired and revoked MCP keys',
)]
class KeyGcCommand extends Command
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $consoleOutput): int
    {
        $io = new SymfonyStyle($input, $consoleOutput);

        $purged = $this->apiKeyService->gc();
        $io->success($purged . ' key(s) purged.');

        return ExitCode::OK;
    }
}
