<?php

namespace ClickAndMortar\AkeneoUtilsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Click And Mortar Akeneo Utils bundle
 *
 * @author  Simon CARRE <simon.carre@clickandmortar.fr>
 * @package ClickAndMortar\AkeneoUtilsBundle
 */
class ClickAndMortarAkeneoUtilsBundle extends Bundle
{
    /**
     * Build.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}