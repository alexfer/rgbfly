<?php declare(strict_types=1);

namespace Inno\MessageHandler;

use Inno\Message\MessageNotification;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MessageNotificationHandler
{
    const int TTL = 3600 * 24;

    /**
     * @param HubInterface $hub
     */
    public function __construct(
        private readonly HubInterface $hub,
    )
    {
    }

    /**
     * @param MessageNotification $message
     * @return void
     */
    public function __invoke(MessageNotification $message): void
    {
        $data = json_decode($message->getAnswer(), true);

        $update = new Update(
            '/hub/' . $data['recipient_id'],
            json_encode(['update' => [
                'createdAt' => $data['createdAt']->format('F j, H:i'),
                'sender' => $data['from'],
                'count' => $data['count'],
                'message' => $data['message'],
            ]]),
        );

        $this->hub->publish($update);
    }
}