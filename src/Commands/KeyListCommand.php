<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Commands;

use Blackcube\Mcp\Models\ApiKey;
use Blackcube\Mcp\Services\ApiKeyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Console command listing the MCP keys.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
#[AsCommand(
    name: 'mcp:keyList',
    description: 'List MCP keys',
)]
class KeyListCommand extends Command
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('show-expired', null, InputOption::VALUE_NONE, 'Include expired keys');
    }

    protected function execute(InputInterface $input, OutputInterface $consoleOutput): int
    {
        $io = new SymfonyStyle($input, $consoleOutput);
        $includeExpired = (bool) $input->getOption('show-expired');

        $keyRows = [];
        /** @var ApiKey $apiKey */
        foreach ($this->apiKeyService->getKeys($includeExpired) as $apiKey) {
            $keyRows[] = [
                $apiKey->getId(),
                $apiKey->getName(),
                implode(', ', $apiKey->getScopes() ?? []),
                $apiKey->getDateExpire()?->format('Y-m-d H:i'),
                $apiKey->isRevoked() === true ? 'yes' : 'no',
                $apiKey->getDateLastUse()?->format('Y-m-d H:i') ?? '-',
            ];
        }

        $io->table([
            'id',
            'name',
            'scopes',
            'expires',
            'revoked',
            'last use',
        ], $keyRows);

        return ExitCode::OK;
    }
}
