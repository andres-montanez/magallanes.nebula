<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
{
    protected EntityManagerInterface $entityManager;
    protected UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        $repository = $this->entityManager->getRepository(User::class);
        return $repository->findBy([], ['name' => 'ASC']);
    }

    /**
     * @return User|null
     */
    public function getByUsername(string $username): ?User
    {
        $repository = $this->entityManager->getRepository(User::class);
        return $repository->findOneBy(['username' => $username]);
    }

    public function create(User $user)
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function update(User $user)
    {
        $this->entityManager->flush();
    }

    public function encodePassword(User $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($user, $plainPassword);
    }
}
