<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Tools;

/**
 * Declarative registry of every tool shipped by the server.
 * McpServerFactory registers them (filtered by the key scopes) and
 * InfoService derives the tool surface of info.model from the same list,
 * so the documented surface can never drift from the real one.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class Registry
{
    /**
     * @var class-string[]
     */
    public const TOOLS = [
        Info\Model::class,
        Info\Stats::class,
        Content\Index::class,
        Content\Search::class,
        Content\Get::class,
        Content\Create::class,
        Content\Move::class,
        Content\Update::class,
        Content\Tag::class,
        Tag\Index::class,
        Tag\Search::class,
        Tag\Get::class,
        Tag\Create::class,
        Tag\Move::class,
        Tag\Update::class,
        Tag\Content::class,
        Menu\Index::class,
        Menu\Get::class,
        Menu\Create::class,
        Menu\Update::class,
        Menu\Move::class,
        Menu\Delete::class,
        LlmMenu\Index::class,
        LlmMenu\Get::class,
        LlmMenu\Create::class,
        LlmMenu\Update::class,
        LlmMenu\Move::class,
        LlmMenu\Delete::class,
        Md\Export::class,
        Md\Import::class,
        Translation\Link::class,
        Translation\Unlink::class,
        Slug\Index::class,
        Slug\Get::class,
        Slug\Create::class,
        Slug\Update::class,
        Slug\Delete::class,
        Xeo\Get::class,
        Xeo\Refresh::class,
        GlobalXeo\Index::class,
        GlobalXeo\Get::class,
        GlobalXeo\Set::class,
        GlobalXeo\Delete::class,
        File\Fetch::class,
        File\Upload::class,
        Type\Index::class,
        Type\Create::class,
        Type\Update::class,
        ElasticSchema\Index::class,
        ElasticSchema\Create::class,
        ElasticSchema\Update::class,
        SchemaMapping\Index::class,
        SchemaMapping\Create::class,
        SchemaMapping\Update::class,
        SchemaMapping\Delete::class,
        Parameter\Index::class,
        Parameter\Create::class,
        Parameter\Update::class,
        Parameter\Delete::class,
        Host\Index::class,
        Host\Create::class,
        Host\Update::class,
        Language\Index::class,
        Language\Create::class,
        Language\Update::class,
        Author\Index::class,
        Author\Create::class,
        Author\Update::class,
    ];
}
