<?php

namespace Application\Factory\Controller;

use Application\Controller\IndexController;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return IndexController
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $serviceLocator = $serviceLocator->getServiceLocator();
        $preParser      = $serviceLocator->get('PreParser');
        $parser         = $serviceLocator->get('Parser');

        return new IndexController($preParser, $parser);
    }
}