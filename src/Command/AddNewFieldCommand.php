<?php

namespace App\Command;

use App\Repository\DynamicFormRepository;
use App\Repository\DynamicFormSectionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'installer:add-field')]
class AddNewFieldCommand extends Command
{
    public function __construct(
        private readonly DynamicFormRepository $dynamicFormRepository,
        private readonly DynamicFormSectionRepository $dynamicFormSectionRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'the key of the field you want to add')
            ->addArgument('label', InputArgument::REQUIRED, 'the label of the field you want to add')
            ->addOption('table', null,InputOption::VALUE_REQUIRED, 'The table you want to add a field to')
            ->addOption('form', null,InputOption::VALUE_REQUIRED, 'the form you want to add a field to')
            ->addOption('type', null,InputOption::VALUE_REQUIRED, 'the type of the field you want to add')
            ->addOption('section', null,InputOption::VALUE_REQUIRED, 'the section in the form of the field you want to add')
            ->addOption('columns', null,InputOption::VALUE_REQUIRED, 'the amount of columns for the field (1-12)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // required options
        $requiredOptions = ['table', 'form', 'type', 'section'];
        foreach ($requiredOptions as $option) {
            if (!$this->checkRequiredOption($input, $output, $option)) {
                return Command::FAILURE;
            }
        }

        // set default data for optional options
        $columns = $input->getOption('columns') ? $input->getOption('columns') : 12;

        // get form
        $form = $this->dynamicFormRepository->findOneBy(['formKey' => $input->getOption('form')]);
        if (!$form) {
            $output->writeln([
                'Form ' . $input->getOption('form') . ' not found',
                '============',
            ]);
            return Command::FAILURE;
        }

        // get section
        $section = $this->dynamicFormSectionRepository->findOneBy([
            'sectionKey' => $input->getOption('section'),
            'form' => $form,
        ]);
        if (!$section) {
            $output->writeln([
                'Section ' . $input->getOption('section') . ' not found',
                '============',
            ]);
            return Command::FAILURE;
        }

        // start
        $output->writeln([
            'Add new ' . $input->getOption('type') . ' field to '
            . $input->getOption('table')
            . ' / ' . $input->getOption('form'),
            '============',
            '',
        ]);

        // alter table sql

        // insert to dynamic_form

        // insert to dynamic_form_field

        // done
        $output->writeln([
            'Field created',
            '============',
            '',
        ]);

        return Command::SUCCESS;
    }

    private function checkRequiredOption($input, $output, $option): bool
    {
        if (!$input->getOption($option)) {
            $output->writeln([
                'you must specify a ' . $option,
                '============',
            ]);
            return false;
        }

        return true;
    }
}