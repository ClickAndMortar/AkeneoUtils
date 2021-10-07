<?php

namespace ClickAndMortar\AkeneoUtilsBundle\Command;

use Akeneo\Pim\Enrichment\Bundle\Elasticsearch\ProductAndProductModelQueryBuilderFactory;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\Filter\Operators;
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
    protected static $defaultName = 'candm:akeneo-utils:clear-models-without-children';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ProductAndProductModelQueryBuilderFactory
     */
    protected $productModelQueryBuilderFactory;

    /**
     * @param EntityManager                             $entityManager
     * @param ProductAndProductModelQueryBuilderFactory $productModelQueryBuilderFactory
     */
    public function __construct(
        EntityManager $entityManager,
        ProductAndProductModelQueryBuilderFactory $productModelQueryBuilderFactory
    )
    {
        parent::__construct(null);
        $this->entityManager                   = $entityManager;
        $this->productModelQueryBuilderFactory = $productModelQueryBuilderFactory;
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
        $output->writeln('<info>Process empty sub models...</info>');
        /** @var ProductModelInterface[] $subModels */
        $subModels = $this->productModelQueryBuilderFactory->create()
                                                           ->addFilter('entity_type', Operators::EQUALS, ProductModelInterface::class)
                                                           ->addFilter('parent', Operators::IS_NOT_EMPTY, null)
                                                           ->execute();
        foreach ($subModels as $subModel) {
            $products = $subModel->getProducts();
            if (count($products) > 0) {
                continue;
            }

            // Delete sub model
            $output->writeln(sprintf('<info>%s</info>', $subModel->getCode()));
            $subModel = $this->entityManager->merge($subModel);
            $this->entityManager->remove($subModel);
        }
        $this->entityManager->flush();

        $output->writeln('<info>Process empty models...</info>');
        /** @var ProductModelInterface[] $models */
        $models = $this->productModelQueryBuilderFactory->create()
                                                        ->addFilter('entity_type', Operators::EQUALS, ProductModelInterface::class)
                                                        ->addFilter('parent', Operators::IS_EMPTY, null)
                                                        ->execute();
        foreach ($models as $model) {
            $subModels = $model->getProductModels();
            if (count($subModels) > 0) {
                continue;
            }

            // Delete model
            $output->writeln(sprintf('<info>%s</info>', $model->getCode()));
            $model = $this->entityManager->merge($model);
            $this->entityManager->remove($model);
        }
        $this->entityManager->flush();
    }
}
