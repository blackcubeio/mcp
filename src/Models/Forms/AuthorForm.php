<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Models\Forms;

use Blackcube\BridgeFormModel\BridgeFormModel;
use Blackcube\BridgeFormModel\Attributes\Bridge;
use Blackcube\Mcp\Traits\PatchScenarioTrait;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Url;

/**
 * Author form model (ported from dboard, UI labels stripped).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class AuthorForm extends BridgeFormModel
{
    use PatchScenarioTrait;

    protected string $firstname = '';
    protected string $lastname = '';
    protected ?string $email = null;
    protected ?string $jobTitle = null;
    protected ?string $worksFor = null;
    protected ?string $knowsAbout = null;
    protected ?string $sameAs = null;
    protected ?string $url = null;
    protected ?string $image = null;
    protected bool $active = true;

    /**
     * {@inheritdoc}
     */
    public function load(mixed $submittedValues, ?string $scope = null): bool
    {
        $this->active = false;
        return parent::load($submittedValues, $scope);
    }

    #[Bridge]
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }
    #[Bridge]
    public function getFirstname(): string
    {
        return $this->firstname;
    }
    #[Bridge]
    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }
    #[Bridge]
    public function getLastname(): string
    {
        return $this->lastname;
    }
    #[Bridge]
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
    #[Bridge]
    public function getEmail(): ?string
    {
        return $this->email;
    }
    #[Bridge]
    public function setJobTitle(?string $jobTitle): void
    {
        $this->jobTitle = $jobTitle;
    }
    #[Bridge]
    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }
    #[Bridge]
    public function setWorksFor(?string $worksFor): void
    {
        $this->worksFor = $worksFor;
    }
    #[Bridge]
    public function getWorksFor(): ?string
    {
        return $this->worksFor;
    }
    #[Bridge]
    public function setKnowsAbout(?string $knowsAbout): void
    {
        $this->knowsAbout = $knowsAbout;
    }
    #[Bridge]
    public function getKnowsAbout(): ?string
    {
        return $this->knowsAbout;
    }
    #[Bridge]
    public function setSameAs(?string $sameAs): void
    {
        $this->sameAs = $sameAs;
    }
    #[Bridge]
    public function getSameAs(): ?string
    {
        return $this->sameAs;
    }
    #[Bridge]
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }
    #[Bridge]
    public function getUrl(): ?string
    {
        return $this->url;
    }
    #[Bridge]
    public function setImage(?string $image): void
    {
        $this->image = $image;
    }
    #[Bridge]
    public function getImage(): ?string
    {
        return $this->image;
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
                'firstname',
                'lastname',
                'email',
                'jobTitle',
                'worksFor',
                'knowsAbout',
                'sameAs',
                'url',
                'image',
                'active',
            ],
            'edit' => [
                'firstname',
                'lastname',
                'email',
                'jobTitle',
                'worksFor',
                'knowsAbout',
                'sameAs',
                'url',
                'image',
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
            'firstname' => [
                new Required(),
                new Length(max: 255),
            ],
            'lastname' => [
                new Required(),
                new Length(max: 255),
            ],
            'email' => [
                new Email(skipOnEmpty: true),
                new Length(max: 255, skipOnEmpty: true),
            ],
            'jobTitle' => [
                new Length(max: 255, skipOnEmpty: true),
            ],
            'worksFor' => [
                new Length(max: 5000, skipOnEmpty: true),
            ],
            'knowsAbout' => [
                new Length(max: 5000, skipOnEmpty: true),
            ],
            'sameAs' => [
                new Length(max: 5000, skipOnEmpty: true),
            ],
            'url' => [
                new Url(skipOnEmpty: true),
                new Length(max: 255, skipOnEmpty: true),
            ],
            'image' => [
                new Length(max: 255, skipOnEmpty: true),
            ],
            'active' => [
                new BooleanValue(),
            ],
        ];
    }
}
