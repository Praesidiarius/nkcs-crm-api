<?php

namespace App\Command;

use App\Repository\ContactRepository;
use App\Repository\SystemSettingRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'installer:send-confirmation')]
class SendInstallConfirmationCommand extends Command
{
    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly MailerInterface         $mailer,
        private readonly SystemSettingRepository $systemSettings,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->addArgument('identifier', InputArgument::REQUIRED, 'The identifier of the user.')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Send Confirmation E-Mail to Contact',
            '============',
            '',
        ]);

        $contact = $this->contactRepository->findByAttribute(
            'contact_identifier',
            $input->getArgument('identifier')
        );

        if (!$contact) {
            $output->writeln([
                'Contact ' . $input->getArgument('identifier') . ' not found',
                '============',
            ]);
            return Command::FAILURE;
        }

        $emailSubject = $this->systemSettings->findOneBy(['settingKey' => 'install-confirm-email-subject']);
        $emailContent = $this->systemSettings->findOneBy(['settingKey' => 'install-confirm-email-content']);
        $emailText = $this->systemSettings->findOneBy(['settingKey' => 'install-confirm-email-text']);
        $emailFrom = $this->systemSettings->findOneBy(['settingKey' => 'install-confirm-email-from']);
        $emailName = $this->systemSettings->findOneBy(['settingKey' => 'contact-signup-email-name']);

        $email = (new Email())
            ->from(new Address($emailFrom->getSettingValue(), $emailName->getSettingValue()))
            ->to($contact->getTextField('email_private'))
            ->priority(Email::PRIORITY_HIGH)
            ->subject($emailSubject->getSettingValue())
            ->text(str_replace([
                '#IDENTIFIER#',
                '#PASS#',
            ], [
                $contact->getTextField('contact_identifier'),
                $input->getArgument('password'),
            ], $emailText->getSettingValue()))
            ->html(str_replace([
                '#IDENTIFIER#',
                '#PASS#',
            ], [
                $contact->getTextField('contact_identifier'),
                $input->getArgument('password'),
            ], $emailContent->getSettingValue()))
        ;

        $this->mailer->send($email);

        $output->writeln([
            'E-Mail sent',
            '============',
            '',
        ]);

        return Command::SUCCESS;
    }
}