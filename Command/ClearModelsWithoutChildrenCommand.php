<?php

namespace ClickAndMortar\AkeneoUtilsBundle\Command;

use Akeneo\Pim\Enrichment\Bundle\Doctrine\ORM\Repository\ProductModelRepository;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\Filter\Operators;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear models without children
 *
 * @author  Simon CARRE <simon.carre@clickandmortar.fr>
 * @package ClickAndMortar\AkeneoUtilsBundle\Command
 */
class ClearModelsWithoutChildrenCommand extends Command
{
    /**
     * Chunk size for flush
     *
     * @var int
     */
    const CHUNK_SIZE = 50;

    protected static $defaultName = 'candm:akeneo-utils:clear-models-without-children';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Connection
     */
    protected $sqlConnection;

    /**
     * @var ProductModelRepository
     */
    protected $productModelRepository;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param EntityManager $entityManager
     * @param Connection    $sqlConnection
     */
    public function __construct(
        EntityManager $entityManager,
        Connection $sqlConnection,
        ProductModelRepository $productModelRepository
    )
    {
        parent::__construct(null);
        $this->entityManager          = $entityManager;
        $this->sqlConnection          = $sqlConnection;
        $this->productModelRepository = $productModelRepository;
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Clear models without children');
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
        $this->output = $output;
        $this->output->writeln('<info>Process empty sub models...</info>');
        $sqlQuery             = <<<SQL
        SELECT pm.code
        FROM pim_catalog_product_model AS pm
        LEFT JOIN pim_catalog_product AS p ON p.product_model_id = pm.id
        WHERE pm.parent_id IS NOT NULL AND p.id IS NULL;
SQL;
        $subModelsIdentifiers = $this->sqlConnection->executeQuery($sqlQuery)->fetchAllAssociativeIndexed();
        $subModelsIdentifiers = array_keys($subModelsIdentifiers);
        $this->deleteModelsByIdentifiers($subModelsIdentifiers);

        $this->output->writeln('<info>Process empty models...</info>');
        $sqlQuery             = <<<SQL
        SELECT rm.code
        FROM pim_catalog_product_model AS rm
        LEFT JOIN pim_catalog_product_model AS sb ON sb.parent_id = rm.id
        WHERE rm.parent_id IS NULL AND sb.id IS NULL;
SQL;
        $modelsIdentifiers = $this->sqlConnection->executeQuery($sqlQuery)->fetchAllAssociativeIndexed();
        $modelsIdentifiers = array_keys($modelsIdentifiers);
        $this->deleteModelsByIdentifiers($modelsIdentifiers);

        return 0;
    }

    /**
     * Delete models by $identifiers
     *
     * @param array $identifiers
     *
     * @return void
     */
    protected function deleteModelsByIdentifiers($identifiers)
    {
        $chunkIndex = 0;
        $models     = $this->productModelRepository->findByIdentifiers($identifiers);
        foreach ($models as $model) {
            $this->output->writeln(sprintf('<info>%s</info>', $model->getCode()));
            $model = $this->entityManager->merge($model);
            $this->entityManager->remove($model);
            $chunkIndex++;
            if ($chunkIndex >= self::CHUNK_SIZE) {
                $this->entityManager->flush();
                $chunkIndex = 0;
            }
        }
        $this->entityManager->flush();
    }
}
