<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\EventListener\ActivityListListener;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;

class ActivityListChainProvider
{
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var  ConfigProvider */
    protected $entityConfigProvider;

    /** @var ActivityListProviderInterface[] */
    protected $providers;

    /**
     * @param ServiceLink    $securityFacadeLink
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(
        ServiceLink $securityFacadeLink,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $entityConfigProvider
    ) {
        $this->securityFacadeLink   = $securityFacadeLink;
        $this->doctrineHelper       = $doctrineHelper;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    public function addProvider(ActivityListProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param object $entity
     *
     * @return bool|ActivityList
     */
    public function getActivityListByActivityEntity($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                $list = new ActivityList();
                $list->setVerb(ActivityListListener::STATE_CREATE);

                $list->setRelatedActivityClass($provider->getActivityClass());
                $list->setRelatedActivityId($provider->getActivityId($entity));

                $list->setSubject($provider->getSubject($entity));
                $list->setOwner($entity->getOwner());
                $list->setOrganization($entity->getOrganization());
                $list->setData($provider->getData($entity));

                $list->setRelatedEntityClass($this->doctrineHelper->getEntityClass($entity));
                $list->setRelatedEntityId($this->doctrineHelper->getSingleEntityIdentifier($entity));

                return $list;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getActivityListOption()
    {
        $templates = [];
        foreach ($this->providers as $provider) {
            $entityConfig = $this->entityConfigProvider->getConfig($provider->getActivityClass());
            $templates[$provider->getActivityClass()] = [
                'icon'     => $entityConfig->get('icon'),
                'label'    => $entityConfig->get('label'),
                'template' => $provider->getTemplate(),
                'routes'   => $provider->getRoutes(),
            ];
        }

        return $templates;
    }

    /**
     * @param object $entity
     *
     * @return string|null
     */
    public function getSubject($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                return $provider->getSubject($entity);
            }
        }

        return null;
    }
}
