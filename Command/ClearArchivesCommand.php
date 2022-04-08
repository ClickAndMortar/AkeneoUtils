<?php

namespace ClickAndMortar\AkeneoUtilsBundle\Command;

use Symfony\Component\Console\Command\Command;
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
class ClearArchivesCommand extends Command
{
    protected static $defaultName = 'candm:akeneo-utils:clear-archives';

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
        $this->setDescription('Clear archives directories to avoid large disk usage')
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
        // Check archives directory
        $archivesPath = $input->getArgument('path');
        if (!is_dir($archivesPath)) {
            $output->writeln('<error>Invalid archives path.</error>');

            return;
        }

        // Delete old directories
        $limitDateAsString    = sprintf('-%s days', $input->getOption('days'));
        $limitDate            = strtotime($limitDateAsString);
        $archivesDeletedCount = 0;
        $exportArchivesPaths  = $this->getSubDirectories($archivesPath);
        foreach ($exportArchivesPaths as $exportArchivesPath) {
            foreach ($this->getSubDirectories($exportArchivesPath) as $archivePath) {
                $creationDate = filemtime($archivePath);
                if ($creationDate < $limitDate) {
                    $output->writeln(sprintf('<info>%s</info>', $archivePath));
                    $this->recursiveDirectoryRemove($archivePath);
                    $archivesDeletedCount++;
                }
            }
        }

        $output->writeln(sprintf('<info>%s archives directories removed.</info>', $archivesDeletedCount));

        return 0;
    }

    /**
     * Get subdirectories by $path
     *
     * @param string $path
     *
     * @return array
     */
    protected function getSubDirectories($path)
    {
        $subDirectories = array();
        if (is_dir($path)) {
            $subDirectories = glob(sprintf('%s/*', $path, GLOB_ONLYDIR));
        }

        return $subDirectories;
    }

    /**
     * Recursive remove for a directory
     *
     * @param string $path
     *
     * @return void
     */
    protected function recursiveDirectoryRemove($path)
    {
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($path . "/" . $object)) {
                        $this->recursiveDirectoryRemove($path . "/" . $object);
                    } else {
                        unlink($path . "/" . $object);
                    }
                }
            }
            rmdir($path);
        }
    }
}
