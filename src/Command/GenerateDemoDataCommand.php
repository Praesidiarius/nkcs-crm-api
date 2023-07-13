<?php

namespace App\Command;

use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Faker\Factory;

#[AsCommand(name: 'installer:generate-demo-data')]
class GenerateDemoDataCommand extends Command
{
    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly ContactAddressRepository $addressRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'the type of demo data you want to generate (contact/item/job)')
            ->addOption('amount', null,InputOption::VALUE_REQUIRED, 'The amount of data you want to generate')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $allowedDataTypes = ['contact'];
        $dataType = $input->getArgument('type');
        if (!$dataType || !in_array($dataType, $allowedDataTypes)) {
            $output->writeln([
                'Invalid DataType ' . $dataType,
                '============',
                '',
            ]);
            return Command::FAILURE;
        }

        $amount = $input->getOption('amount') ? $input->getOption('amount') : 1;
        if ($amount <= 0 || $amount > 1000000) {
            $output->writeln([
                'Invalid amount - choose a number between 1 (default) and 1 Million ',
                '============',
                '',
            ]);
            return Command::FAILURE;
        }

        switch ($dataType) {
            case 'contact':
                $this->createContactDemoData($amount, $output);
                break;
            default:
                break;
        }

        return Command::SUCCESS;

    }

    private function createContactDemoData($amount, $output): void
    {
        $output->writeln([
            'Creating Contact Demo Data: ' . $amount . ' Contacts',
            '============',
            '',
        ]);

        $faker = Factory::create();

        for ($i = 0; $i < $amount; $i++) {
            $contact = $this->contactRepository->getDynamicDto();
            $contact->setBoolField('is_company', false);
            $contact->setIntField('salution_id', rand(1, 2));
            $contact->setTextField('first_name', $faker->firstName);
            $contact->setTextField('last_name', $faker->lastName);
            $contact->setTextField('email_private', $faker->email);
            $contact->setTextField('phone', $faker->phoneNumber);
            $contact->setCreatedBy(0);
            $contact->setCreatedDate();
            $contact->setTextField('description', 'Randomly generated Demo Data');

            $contact = $this->contactRepository->save($contact);

            $address = $this->addressRepository->getDynamicDto();
            $address->setIntField('contact_id', $contact->getId());
            $address->setTextField('street', $faker->streetAddress);
            $address->setTextField('zip', $faker->numberBetween(1111, 9000));
            $address->setTextField('city', $faker->city);

            $this->addressRepository->save($address);
        }

        $output->writeln([
            $amount . ' Contacts created successfully',
            '============',
            '',
        ]);
    }
}