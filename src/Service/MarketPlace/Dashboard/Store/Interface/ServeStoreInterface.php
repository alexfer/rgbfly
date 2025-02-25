<?php

namespace Inno\Service\MarketPlace\Dashboard\Store\Interface;

use Inno\Entity\MarketPlace\Store;
use Symfony\Component\Security\Core\User\UserInterface;

interface ServeStoreInterface
{
    /**
     * @param UserInterface $user
     * @return Store|null
     */
    public function supports(UserInterface $user): ?Store;

    /**
     * @return array|null
     */
    public function extra(): ?array;

    /**
     * @return array
     */
    public function currency(): array;
}