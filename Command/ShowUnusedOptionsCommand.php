<?php

namespace ClickAndMortar\AkeneoUtilsBundle\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Pim\Bundle\CatalogBundle\Doctrine\ORM\Repository\AttributeOptionRepository;
use Pim\Bundle\CatalogBundle\Doctrine\ORM\Repository\AttributeRepository;
use Pim\Bundle\CatalogBundle\Doctrine\ORM\Repository\FamilyRepository;
use Pim\Bundle\CatalogBundle\Elasticsearch\ProductQueryBuilderFactory;
use Akeneo\Pim\Enrichment\Bundle\Elasticsearch\ProductAndProductModelQueryBuilderFactory;
use Pim\Bundle\CatalogBundle\Entity\Attribute;
use Pim\Bundle\CatalogBundle\Entity\Family;
use Pim\Component\Catalog\AttributeTypes;
use Pim\Component\Catalog\Model\AttributeOptionInterface;
use Pim\Component\Catalog\Model\FamilyVariant;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show unused options for given family and attribute code
 *
 * @author  Simon CARRE <simon.carre@clickandmortar.fr>
 * @package ClickAndMortar\AkeneoUtilsBundle\Command
 */
class ShowUnusedOptionsCommand extends Command
{
    protected static $defaultName = 'candm:akeneo-utils:list-unused-options';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var FamilyRepository
     */
    protected $familyRepository;

    /**
     * @var AttributeOptionRepository
     */
    protected $attributeOptionRepository;

    /**
     * @var ProductAndProductModelQueryBuilderFactory
     */
    protected $productAndProductModelQueryBuilderFactory;

    /**
     * ShowUnusedOptionsCommand constructor.
     *
     * @param EntityManager                             $entityManager
     * @param ProductAndProductModelQueryBuilderFactory $productAndProductModelQueryBuilderFactory
     */
    public function __construct(EntityManager $entityManager, ProductAndProductModelQueryBuilderFactory $productAndProductModelQueryBuilderFactory)
    {
        parent::__construct(null);
        $this->entityManager                             = $entityManager;
        $this->productAndProductModelQueryBuilderFactory = $productAndProductModelQueryBuilderFactory;
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('candm:akeneo-utils:list-unused-options')
             ->setDescription('Show unused options for given family and attribute code')
             ->addOption('family', 'f', InputOption::VALUE_REQUIRED, 'Family code')
             ->addOption('attribute', 'a', InputOption::VALUE_OPTIONAL, 'Attribute code to check options');
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
        $this->loadRepositories();

        // Check family
        $familyCode = $input->getOption('family');
        /** @var Family $family */
        $family = $this->familyRepository->findOneByIdentifier($familyCode);
        if ($family === null) {
            $this->output->writeln('<error>Bad family code.</error>');

            return;
        }

        // Get attribute code to filter
        $attributeCodeFilter = $input->getOption('attribute');

        // Classic family or family variant
        $attributesCodesPerType = [
            'model'   => [],
            'product' => [],
        ];
        /** @var FamilyVariant[] $familiesVariant */
        $familiesVariant = $family->getFamilyVariants();
        if (empty($familiesVariant)) {
            // Just simple family
            $attributes                        = $family->getAttributes();
            $attributesCodesPerType['product'] = $this->getAttributesOptionsCodes($attributes, $attributeCodeFilter);
        } else {
            // Family variant
            foreach ($familiesVariant as $familyVariant) {
                // Model attributes
                $commonAttributes                = $familyVariant->getCommonAttributes();
                $attributesCodesPerType['model'] = $this->getAttributesOptionsCodes($commonAttributes, $attributeCodeFilter);

                // Product attributes
                $attributes                        = $familyVariant->getAttributes();
                $attributesCodesPerType['product'] = $this->getAttributesOptionsCodes($attributes, $attributeCodeFilter);
            }
        }

        // Search unused options
        $unusedOptionsPerAttribute = [];
        foreach ($attributesCodesPerType as $type => $attributesCodes) {
            foreach ($attributesCodes as $attributeCode) {
                $options = $this->getOptionsByAttributeCode($attributeCode);
                foreach ($options as $option) {
                    $products = $this->productAndProductModelQueryBuilderFactory->create()
                                                                                ->addFilter($attributeCode, 'IN', [$option])
                                                                                ->execute();
                    if ($products->count() === 0) {
                        if (!isset($unusedOptionsPerAttribute[$attributeCode])) {
                            $unusedOptionsPerAttribute[$attributeCode] = [];
                        }
                        $unusedOptionsPerAttribute[$attributeCode][] = $option;
                    }
                }
            }
        }

        // Print result
        $this->displayUnusedOptions($unusedOptionsPerAttribute);

        return;
    }

    /**
     * Load repositories
     *
     * @return void
     */
    protected function loadRepositories()
    {
        $this->familyRepository                          = $this->entityManager->getRepository('PimCatalogBundle:Family');
        $this->attributeOptionRepository                 = $this->entityManager->getRepository('PimCatalogBundle:AttributeOption');
    }

    /**
     * Get only attributes options code
     *
     * @param Collection $attributes
     * @param string     $attributeCodeFilter Return only attribute with this code
     *
     * @return array
     */
    protected function getAttributesOptionsCodes($attributes, $attributeCodeFilter = null)
    {
        $attributesCodes = [];
        foreach ($attributes as $attribute) {
            if (
                ($attributeCodeFilter === null || $attributeCodeFilter === $attribute->getCode())
                && ($attribute->getType() === AttributeTypes::OPTION_SIMPLE_SELECT || $attribute->getType() === AttributeTypes::OPTION_MULTI_SELECT)
            ) {
                $attributesCodes[] = $attribute->getCode();
            }
        }

        return $attributesCodes;
    }

    /**
     * Get options codes by $attributeCode
     *
     * @param string $attributeCode
     *
     * @return array
     */
    protected function getOptionsByAttributeCode($attributeCode)
    {
        $options = $this->attributeOptionRepository->createQueryBuilder('o')
                                                   ->select('o . code')
                                                   ->innerJoin('o . attribute', 'a')
                                                   ->where('a . code = :attribute_code')
                                                   ->setParameter('attribute_code', $attributeCode)
                                                   ->getQuery()
                                                   ->getResult();

        return array_column($options, 'code');
    }

    /**
     * Display unused options
     *
     * @param array $unusedOptionsPerAttribute
     *
     * @return void
     */
    protected function displayUnusedOptions($unusedOptionsPerAttribute)
    {
        if (empty($unusedOptionsPerAttribute)) {
            $this->output->writeln('<info>No unused options.</info>');

            return;
        }

        foreach ($unusedOptionsPerAttribute as $attributeCode => $unusedOptions) {
            $this->output->writeln(sprintf('<info>Attribute "%s":</info>', $attributeCode));
            foreach ($unusedOptions as $unusedOption) {
                $this->output->writeln(sprintf('<info>- %s</info>', $unusedOption));
            }
        }

        return;
    }
}
