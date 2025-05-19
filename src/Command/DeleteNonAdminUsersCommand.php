<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:delete-non-admin-users',
    description: 'Deletes all users except administrators'
)]
class DeleteNonAdminUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userRepository = $this->entityManager->getRepository(User::class);
        $nonAdminUsers = $userRepository->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        if (empty($nonAdminUsers)) {
            $io->success('No non-admin users found to delete.');
            return Command::SUCCESS;
        }

        $count = count($nonAdminUsers);
        
        foreach ($nonAdminUsers as $user) {
            $this->entityManager->remove($user);
        }
        
        $this->entityManager->flush();

        $io->success(sprintf('Successfully deleted %d non-admin user(s).', $count));

        return Command::SUCCESS;
    }
} 