<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email',
    description: 'Send a test email to verify mailer configuration.'
)]
class TestEmailCommand extends Command
{
    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $email = (new Email())
                ->from('Mojo.2025.jojo@gmail.com')
                ->to('Mojo.2025.jojo@gmail.com')
                ->subject('Test email from E-commerce')
                ->text('This is a test email to verify the mailer configuration.')
                ->html('<p>This is a test email to verify the mailer configuration.</p>');

            $this->mailer->send($email);
            $output->writeln('Test email sent successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Error sending email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 