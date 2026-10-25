<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Forms;

use Blackcube\Dcore\Models\Slug;
use Blackcube\BridgeFormModel\BridgeFormModel;
use Blackcube\BridgeFormModel\Attributes\Bridge;
use Blackcube\Mcp\Traits\PatchScenarioTrait;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Url;

/**
 * Slug form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class SlugForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected ?int $id = null;
    protected int $hostId = 1;
    protected string $path = '';
    protected ?string $targetUrl = null;
    protected ?int $httpCode = null;
    protected bool $active = false;

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
        $this->active = false;
        return parent::load($submittedValues, $scope);
    }

    #[Bridge]
    public function setId(?int $id): void
    {
        $this->id = $id;
    }
    #[Bridge]
    public function getId(): ?int
    {
        return $this->id;
    }
    #[Bridge]
    public function setHostId(int $hostId): void
    {
        $this->hostId = $hostId;
    }
    #[Bridge]
    public function getHostId(): int
    {
        return $this->hostId;
    }
    #[Bridge]
    public function setPath(string $path): void
    {
        $this->path = ltrim($path, '/');
    }
    #[Bridge]
    public function getPath(): string
    {
        return $this->path;
    }
    #[Bridge]
    public function setTargetUrl(?string $targetUrl): void
    {
        $this->targetUrl = $targetUrl;
    }
    #[Bridge]
    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }
    #[Bridge]
    public function setHttpCode(?int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }
    #[Bridge]
    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }
    #[Bridge]
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
    #[Bridge]
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return [
            'create' => [
                'hostId',
                'path',
                'active',
            ],
            'edit' => [
                'hostId',
                'path',
                'active',
            ],
            'redirect' => [
                'hostId',
                'path',
                'targetUrl',
                'httpCode',
                'active',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            'hostId' => [
                new Required(),
                new Integer(min: 1),
            ],
            'path' => [
                new Length(max: 255),
                new Callback(callback: $this->checkUniquePath(...)),
            ],
            'active' => [
                new BooleanValue(),
            ],
            'targetUrl' => [
                new Url(skipOnEmpty: true),
                new Length(max: 512),
            ],
            'httpCode' => [
                new In([
                    null,
                    301,
                    302,
                    307,
                    308,
                ], skipOnEmpty: true),
            ],
        ];
    }

    protected function checkUniquePath(mixed $path): Result
    {
        $validationResult = new Result();
        $normalizedPath = ltrim((string) $path, '/');
        $slugsQuery = Slug::query()
            ->andWhere([
                'hostId' => $this->getHostId(),
            ])
            ->andWhere([
                'path' => $normalizedPath,
            ]);
        if ($this->getId() !== null) {
            $slugsQuery->andWhere([
                '!=',
                'id',
                $this->getId(),
            ]);
        }
        if ($slugsQuery->exists() === true) {
            $validationResult->addError('This path already exists for this host.');
        }
        return $validationResult;
    }
}
