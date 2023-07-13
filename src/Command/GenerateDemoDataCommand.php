<?php

namespace App\Command;

use App\Entity\JobPosition;
use App\Enum\JobVatMode;
use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use App\Repository\ItemRepository;
use App\Repository\ItemUnitRepository;
use App\Repository\JobPositionRepository;
use App\Repository\JobRepository;
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
        private readonly ItemRepository $itemRepository,
        private readonly ItemUnitRepository $itemUnitRepository,
        private readonly JobRepository $jobRepository,
        private readonly JobPositionRepository $jobPositionRepository,
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
        $allowedDataTypes = ['contact', 'item', 'job'];
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
            case 'item':
                $this->createItemDemoData($amount, $output);
                break;
            case 'job':
                $this->createJobDemoData($amount, $output);
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
            // make every 5th contact a company
            if ($i % 5 === 0) {
                $contact->setBoolField('is_company', true);
                $contact->setTextField('company_name', $faker->company);
                $contact->setTextField('email_private', $faker->companyEmail);
                $contact->setTextField('phone', $faker->phoneNumber);
                $contact->setCreatedBy(0);
                $contact->setCreatedDate();
                $contact->setTextField('description', 'Randomly generated Demo Data');
            } else {
                $gender = rand(1, 2);
                $contact->setBoolField('is_company', false);
                $contact->setIntField('salution_id', $gender);
                $contact->setTextField('first_name', $gender === 1 ? $faker->firstNameMale : $faker->firstNameFemale);
                $contact->setTextField('last_name', $faker->lastName);
                $contact->setTextField('email_private', $faker->email);
                $contact->setTextField('phone', $faker->phoneNumber);
                $contact->setCreatedBy(0);
                $contact->setCreatedDate();
                $contact->setTextField('description', 'Randomly generated Demo Data');
            }

            $contact = $this->contactRepository->save($contact);

            $address = $this->addressRepository->getDynamicDto();
            $address->setIntField('contact_id', $contact->getId());
            $address->setTextField('street', $faker->streetName . ' ' . $faker->buildingNumber);
            $address->setTextField('zip', substr($faker->postcode,1));
            $address->setTextField('city', $faker->city);

            $this->addressRepository->save($address);
        }

        $output->writeln([
            $amount . ' Contacts created successfully',
            '============',
            '',
        ]);
    }

    private function createItemDemoData($amount, $output): void
    {
        $output->writeln([
            'Creating Item Demo Data: ' . $amount . ' Items',
            '============',
            '',
        ]);

        $faker = Factory::create();

        for ($i = 0; $i < $amount; $i++) {
            $item = $this->itemRepository->getDynamicDto();

            $item->setTextField('name', $faker->word);
            $item->setIntField('unit_id', 1);
            $item->setPriceField('price', $faker->randomFloat(2, 5, 500));
            $item->setCreatedBy(0);
            $item->setCreatedDate();
            $item->setTextField('description', 'Randomly generated Demo Data');

            $this->itemRepository->save($item);
        }

        $output->writeln([
            $amount . ' Item created successfully',
            '============',
            '',
        ]);
    }

    private function createJobDemoData($amount, $output): void
    {
        $output->writeln([
            'Creating Job Demo Data: ' . $amount . ' Jobs',
            '============',
            '',
        ]);

        $faker = Factory::create();

        $contactIds = [];
        $contacts = $this->contactRepository->findAll();
        foreach ($contacts as $contact) {
            $contactIds[] = $contact->getId();
        }
        $maxContactIndex = count($contactIds) - 1;

        $cachedItems = [];
        $items = $this->itemRepository->findAll();
        foreach ($items as $item) {
            $cachedItems[] = $item;
        }
        $maxItemIndex = count($cachedItems) - 1;

        $testVatRate = 7.7;

        $itemUnitPiece = $this->itemUnitRepository->find(1);

        for ($i = 0; $i < $amount; $i++) {
            $job = $this->jobRepository->getDynamicDto();

            $job->setIntField('type_id', 1);
            $job->setIntField('contact_id', $contactIds[rand(0,$maxContactIndex)]);
            $job->setTextField('title', 'Demo Job ' . $i + 1);
            $job->setTextField('description', 'Randomly generated Demo Data');
            $job->setIntField('vat_mode', JobVatMode::VAT_EXCLUDED->value);
            $job->setCreatedBy(0);
            $job->setCreatedDate();

            $job = $this->jobRepository->save($job);

            $jobSubTotal = 0;

            $jobPositionCount = rand(1, 10);
            for($y = 0; $y < $jobPositionCount;$y++) {
                $jobPosition = new JobPosition();
                $jobPosition->setJobId($job->getId());
                $jobPosition->setItemId($cachedItems[rand(0, $maxItemIndex)]?->getId() ?? 0);
                $jobPosition->setAmount(rand(1, 5));
                $jobPosition->setUnit($itemUnitPiece);

                $this->jobPositionRepository->save($jobPosition, true);

                $jobSubTotal += $jobPosition->getAmount() * $cachedItems[rand(0, $maxItemIndex)]?->getPriceField('price') ?? 0;
            }

            $job->setPriceField('sub_total', $jobSubTotal);
            $job->setPriceField('vat_rate', $testVatRate);
            $jobVat = $jobSubTotal * ($testVatRate / 100);
            $job->setPriceField('vat_total', $jobVat);

            $total = $jobSubTotal + $jobVat;

            $job->setPriceField('total', $total);

            $this->jobRepository->save($job);

        }

        $output->writeln([
            $amount . ' Jobs created successfully',
            '============',
            '',
        ]);
    }
}