<?php

namespace App\Entity\Contract;

/**
 * Entities implementing this interface get their timestamps filled
 * automatically by App\Doctrine\Listener\TimestampableListener.
 */
interface TimestampableInterface
{
    public function getCreatedAt(): ?\DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): static;

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static;
}
