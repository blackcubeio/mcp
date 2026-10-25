<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Services;

use Blackcube\Dcore\Services\FileService;
use GuzzleHttp\ClientInterface;
use RuntimeException;
use Throwable;

/**
 * Stores an uploaded file in the temporary area, from base64 bytes (or a
 * data: URI) or from a url the server downloads. The returned ref is a
 * temporary file reference: the element save moves it to its permanent
 * location. Raises a RuntimeException with a clear message on an invalid
 * input or a failed download.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class FileUploadService
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly ClientInterface $httpClient,
    ) {}

    /**
     * @return array{ref: string, filename: string, size: int}
     */
    public function upload(string $filename, ?string $content, ?string $url): array
    {
        if ($content !== null) {
            $raw = $content;
            if (preg_match('#^data:[^;]+;base64,(.+)$#s', $raw, $matches) === 1) {
                $raw = $matches[1];
            }
            $binary = base64_decode($raw, true);
            if ($binary === false) {
                throw new RuntimeException('Invalid base64 content.');
            }
        } elseif ($url !== null) {
            try {
                $httpResponse = $this->httpClient->request('GET', $url, [
                    'timeout' => 20,
                ]);
                $binary = $httpResponse->getBody()->getContents();
            } catch (Throwable $e) {
                throw new RuntimeException('Could not download the url: ' . $e->getMessage(), 0, $e);
            }
        } else {
            throw new RuntimeException('Provide either content (base64) or url.');
        }

        $safeName = basename($filename);
        if ($safeName === '') {
            throw new RuntimeException('Invalid filename.');
        }
        $ref = $this->fileService->getTempAlias() . '/' . bin2hex(random_bytes(8)) . '/' . $safeName;
        $this->fileService->write($ref, $binary);

        return [
            'ref' => $ref,
            'filename' => $safeName,
            'size' => strlen($binary),
        ];
    }
}
