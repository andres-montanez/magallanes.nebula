<?php

namespace App\Command\User;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddCommand extends Command
{
    protected static $defaultName = 'mage:user:add';
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Adds a user')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('name', InputArgument::REQUIRED, 'Name')
            ->addOption('is-admin', null, InputOption::VALUE_NONE, 'Grant Administrator role')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = (string) $input->getArgument('username');
        $name = (string) $input->getArgument('name');
        $isAdmin = (bool) $input->getOption('is-admin');

        $roles = [User::ROLE_USER];
        if ($isAdmin) {
            $roles[] = User::ROLE_ADMINISTRATOR;
        }

        $user = new User();
        $user
            ->setUsername($username)
            ->setName($name)
            ->setRoles($roles)
        ;

        // Random password
        $randomPassword = substr(sha1(random_bytes(14)), 10, 20);
        $encodedPassword = $this->userService->encodePassword($user, $randomPassword);
        $user->setPassword($encodedPassword);
        $this->userService->create($user);

        $output->writeln(sprintf('User created, temp password is: %s', $randomPassword));

        // 10 10   * * *   root    docker exec shlink-php bin/cli visit:locate
        return Command::SUCCESS;
    }
}