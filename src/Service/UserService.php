<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
{
    protected EntityManagerInterface $entityManager;
    protected UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        $repository = $this->entityManager->getRepository(User::class);
        return $repository->findBy([], ['name' => 'ASC']);
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
        return $this->passwordEncoder->encodePassword($user, $plainPassword);
    }
}