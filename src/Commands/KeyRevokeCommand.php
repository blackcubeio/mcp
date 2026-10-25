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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Console command revoking an MCP key.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
#[AsCommand(
    name: 'mcp:keyRevoke',
    description: 'Revoke an MCP key',
)]
class KeyRevokeCommand extends Command
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('id', null, InputOption::VALUE_REQUIRED, 'Key id (the number shown by mcp:keyList)');
    }

    protected function execute(InputInterface $input, OutputInterface $consoleOutput): int
    {
        $io = new SymfonyStyle($input, $consoleOutput);
        $id = $input->getOption('id');

        $status = ExitCode::USAGE;
        if (empty($id) === true) {
            $io->error('The --id option is required.');
        } elseif ($this->apiKeyService->revoke((int) $id) === true) {
            $io->success('Key revoked.');
            $status = ExitCode::OK;
        } else {
            $io->error('No key found for this id.');
            $status = ExitCode::UNSPECIFIED_ERROR;
        }

        return $status;
    }
}
