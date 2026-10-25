<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\Mcp\Mcp\Server;

use Mcp\Event\ResponseEvent;
use Mcp\Schema\Notification\ToolListChangedNotification;
use Mcp\Server\Session\SessionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Minimal PSR-14 dispatcher wired into the MCP server for one job: tell the
 * client to re-fetch tools/list when the exposed tool surface changed since the
 * client last synced.
 *
 * A stateless HTTP server has no standby stream to push a notification on its
 * own. So each request carries a fingerprint of the current tool surface
 * (names + input schemas, computed by McpServerFactory). On every response the
 * SDK dispatches a ResponseEvent here; if the fingerprint differs from the one
 * the client last saw (stored in the session), a tools/list_changed
 * notification is queued onto the session outgoing queue and rides out with the
 * response. The reload therefore happens on the next request after a change
 * (tool call or ping), with no session restart.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ToolListChangedNotifier implements EventDispatcherInterface
{
    private const SESSION_FINGERPRINT_KEY = 'blackcube.toolsFingerprint';

    /**
     * Mirror of Protocol::SESSION_OUTGOING_QUEUE: the session key the transport
     * drains to flush server-to-client messages on the response.
     */
    private const SESSION_OUTGOING_QUEUE = '_mcp.outgoing_queue';

    public function __construct(
        private readonly string $toolsFingerprint,
    ) {}

    public function dispatch(object $event): object
    {
        if ($event instanceof ResponseEvent) {
            $this->reconcile($event->getSession());
        }

        return $event;
    }

    private function reconcile(SessionInterface $session): void
    {
        $seenFingerprint = $session->get(self::SESSION_FINGERPRINT_KEY);
        if ($seenFingerprint === null) {
            $session->set(self::SESSION_FINGERPRINT_KEY, $this->toolsFingerprint);
        } elseif ($seenFingerprint !== $this->toolsFingerprint) {
            $this->queueToolListChanged($session);
            $session->set(self::SESSION_FINGERPRINT_KEY, $this->toolsFingerprint);
        }
    }

    private function queueToolListChanged(SessionInterface $session): void
    {
        $encoded = json_encode(new ToolListChangedNotification(), JSON_THROW_ON_ERROR);
        $queue = $session->get(self::SESSION_OUTGOING_QUEUE, []);
        $queue[] = [
            'message' => $encoded,
            'context' => [
                'type' => 'notification',
            ],
        ];
        $session->set(self::SESSION_OUTGOING_QUEUE, $queue);
    }
}
