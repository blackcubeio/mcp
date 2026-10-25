<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models;

use DateTimeImmutable;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\Event\Handler\DefaultDateTimeOnInsert;
use Yiisoft\ActiveRecord\Event\Handler\SetDateTimeOnUpdate;
use Yiisoft\ActiveRecord\Trait\EventsTrait;

/**
 * ApiKey model - Standalone MCP key with its own scopes, decoupled from dboard.
 *
 * The token is never stored; only its SHA-256 hash is kept (the hash column).
 * The id is a surrogate auto-increment used to identify the key for management.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
#[DefaultDateTimeOnInsert(null, 'dateCreate')]
#[SetDateTimeOnUpdate(null, 'dateUpdate')]
class ApiKey extends ActiveRecord
{
    use EventsTrait;

    protected int $id;
    protected string $name = '';
    protected string $hash = '';
    protected ?array $scopes = null;
    protected ?DateTimeImmutable $dateExpire = null;
    protected bool $revoked = false;
    protected ?DateTimeImmutable $dateLastUse = null;
    protected ?DateTimeImmutable $dateCreate = null;
    protected ?DateTimeImmutable $dateUpdate = null;

    public function events(): array
    {
        return [];
    }

    public function tableName(): string
    {
        return '{{%mcpKeys}}';
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function getScopes(): ?array
    {
        return $this->scopes;
    }

    public function setScopes(?array $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function getDateExpire(): ?DateTimeImmutable
    {
        return $this->dateExpire;
    }

    public function setDateExpire(?DateTimeImmutable $dateExpire): void
    {
        $this->dateExpire = $dateExpire;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function setRevoked(bool $revoked): void
    {
        $this->revoked = $revoked;
    }

    public function getDateLastUse(): ?DateTimeImmutable
    {
        return $this->dateLastUse;
    }

    public function setDateLastUse(?DateTimeImmutable $dateLastUse): void
    {
        $this->dateLastUse = $dateLastUse;
    }

    public function getDateCreate(): ?DateTimeImmutable
    {
        return $this->dateCreate;
    }

    public function getDateUpdate(): ?DateTimeImmutable
    {
        return $this->dateUpdate;
    }
}
