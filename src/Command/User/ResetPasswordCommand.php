<?php

namespace App\Command\User;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ResetPasswordCommand extends Command
{
    protected static $defaultName = 'mage:user:reset-password';
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Resets a user password')
            ->addArgument('username', InputArgument::REQUIRED, 'Username');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = (string) $input->getArgument('username');
        $user = $this->userService->getByUsername($username);

        // Random password
        $randomPassword = substr(sha1(random_bytes(14)), 10, 20);
        $encodedPassword = $this->userService->encodePassword($user, $randomPassword);

        $user->setPassword($encodedPassword);
        $this->userService->update($user);

        $output->writeln(sprintf('New password is: %s', $randomPassword));

        return Command::SUCCESS;
    }
}
