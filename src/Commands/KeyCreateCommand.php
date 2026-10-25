<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Commands;

use Blackcube\Mcp\Services\ApiKeyService;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Console command creating an MCP key.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
#[AsCommand(
    name: 'mcp:keyCreate',
    description: 'Create an MCP key',
)]
class KeyCreateCommand extends Command
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Human-readable label')
            ->addOption('scopes', null, InputOption::VALUE_REQUIRED, 'Comma-separated scopes')
            ->addOption('ttl-days', null, InputOption::VALUE_OPTIONAL, 'Validity in days (hard cap 180)');
    }

    protected function execute(InputInterface $input, OutputInterface $consoleOutput): int
    {
        $io = new SymfonyStyle($input, $consoleOutput);
        $name = $input->getOption('name');
        $scopes = $input->getOption('scopes');
        $ttlDays = $input->getOption('ttl-days');

        $status = ExitCode::USAGE;
        if (empty($name) === true) {
            $io->error('The --name option is required.');
        } elseif (empty($scopes) === true) {
            $io->error('The --scopes option is required.');
        } else {
            try {
                $rawScopes = explode(',', (string) $scopes);
                $scopeList = array_map('trim', $rawScopes);
                $created = $this->apiKeyService->create($name, $scopeList, $ttlDays !== null ? (int) $ttlDays : null);
                $io->success('MCP key #' . $created['id'] . ' created. Copy the token now, it will not be shown again:');
                $io->writeln($created['token']);
                $status = ExitCode::OK;
            } catch (InvalidArgumentException $e) {
                $io->error($e->getMessage());
            }
        }

        return $status;
    }
}
