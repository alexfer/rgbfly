<?php declare(strict_types=1);

namespace Inno\Service\MarketPlace\Store\Message\Interface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;

interface MessageServiceInterface
{
    /**
     * @param array $payload
     * @param UserInterface|null $user
     * @param array|null $exclude
     * @param bool $validate
     * @return array|null
     */
    public function process(array $payload, ?UserInterface $user, ?array $exclude, bool $validate = true): ?array;

    /**
     * @param UserInterface|null $user
     * @return JsonResponse
     */
    public function obtainAndResponse(?UserInterface $user): JsonResponse;

    /**
     * @param UserInterface|null $user
     * @param bool $customer
     * @return array
     */
    public function answer(?UserInterface $user, bool $customer = false): array;
}