<?php

namespace ClickAndMortar\AkeneoUtilsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear archives directories to avoid large disk usage
 *
 * @author  Simon CARRE <simon.carre@clickandmortar.fr>
 * @package ClickAndMortar\Bundle\AkeneoUtilsBundle\Command
 */
class ClearArchivesCommand extends ContainerAwareCommand
{
    /**
     * Days of archives to keep
     *
     * @var int
     */
    const DEFAULT_DAYS_TO_KEEP = 5;

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('candm:akeneo-utils:clear-archives')
             ->setDescription('Clear archives directories to avoid large disk usage')
             ->addArgument('path', InputArgument::REQUIRED, 'Archives directory path')
             ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Days of archives to keep', self::DEFAULT_DAYS_TO_KEEP);
    }

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<error>Sicar : test !</error>');

        return;
    }
}
