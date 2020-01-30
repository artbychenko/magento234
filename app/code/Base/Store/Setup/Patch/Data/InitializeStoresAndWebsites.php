<?php declare(strict_types=1);

/**
 * Create store structure
 *
 * Create website, store groups, store views
 *
 * @author Artem Bychenko <artbychenko@gmail.com>
 * @package Base_Store
 */

namespace Base\Store\Setup\Patch\Data;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Group;
use Magento\Store\Model\Website;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Catalog\Helper\DefaultCategory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Catalog\Helper\DefaultCategoryFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;

/**
 * Class InitializeStoresAndWebsites
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class InitializeStoresAndWebsites implements DataPatchInterface
{

    const US_WEBSITE = 'us_website';

    const US_WEBSITE_TITLE = 'US Website';

    const UA_WEBSITE = 'ua_website';

    const UA_WEBSITE_TITLE = 'UA Website';

    const US_STORE_GROUP = 'us_store_group';

    const US_STORE_GROUP_TITLE = 'US Store Group';

    const UA_STORE_GROUP = 'ua_store_group';

    const UA_STORE_GROUP_TITLE = 'UA Store Group';

    const US_STORE_VIEW_EN = 'us_store_view_en';

    const US_STORE_VIEW_EN_TITLE = 'US English Store View';

    const UA_STORE_VIEW_EN = 'ua_store_view_en';

    const UA_STORE_VIEW_EN_TITLE = 'UA English Store View';

    const UA_STORE_VIEW_UA = 'ua_store_view_ua';

    const UA_STORE_VIEW_UA_TITLE = 'UA Ukrainian Store View';

    const UA_STORE_VIEW_RU = 'ua_store_view_ru';

    const UA_STORE_VIEW_RU_TITLE = 'UA Russian Store View';

    /**
     * @var string[]
     */
    private $websites = [
        self::US_WEBSITE => self::US_WEBSITE_TITLE,
        self::UA_WEBSITE => self::UA_WEBSITE_TITLE
    ];

    /**
     * @var string[][]
     */
    private $storeGroups = [
        self::US_WEBSITE => [
            self::US_STORE_GROUP => self::US_STORE_GROUP_TITLE
        ],
        self::UA_WEBSITE => [
            self::UA_STORE_GROUP => self::UA_STORE_GROUP_TITLE
        ]
    ];

    /**
     * @var string[][]
     */
    private $storeViews = [
        self::US_STORE_GROUP => [
            self::US_STORE_VIEW_EN => self::US_STORE_VIEW_EN_TITLE
        ],
        self::UA_STORE_GROUP => [
            self::UA_STORE_VIEW_EN => self::UA_STORE_VIEW_EN_TITLE,
            self::UA_STORE_VIEW_UA => self::UA_STORE_VIEW_UA_TITLE,
            self::UA_STORE_VIEW_RU => self::UA_STORE_VIEW_RU_TITLE
        ]
    ];

    /**
     * @var string[]
     */
    private $websiteDefaultStoreGroup = [
        self::US_WEBSITE  => self::US_STORE_GROUP,
        self::UA_WEBSITE => self::UA_STORE_GROUP
    ];

    /**
     * @var string[]
     */
    private $storeGroupDefaultStoreView = [
        self::US_STORE_GROUP => self::US_STORE_VIEW_EN,
        self::UA_STORE_GROUP => self::UA_STORE_VIEW_RU
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DefaultCategory
     */
    private $defaultCategory;

    /**
     * @var DefaultCategoryFactory
     */
    private $defaultCategoryFactory;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * @var StoreFactory
     */
    private $storeFactory;

    /**
     * @var WebsiteResourceModel
     */
    private $websiteResourceModel;

    /**
     * PatchInitial constructor.
     *
     * @param LoggerInterface $logger
     * @param DefaultCategoryFactory $defaultCategoryFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param GroupFactory $groupFactory
     * @param WebsiteFactory $websiteFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param StoreFactory $storeFactory
     * @param WebsiteResourceModel $websiteResourceModel
     */
    public function __construct(
        LoggerInterface $logger,
        DefaultCategoryFactory $defaultCategoryFactory,
        StoreRepositoryInterface $storeRepository,
        WebsiteRepositoryInterface $websiteRepository,
        GroupRepositoryInterface $groupRepository,
        GroupFactory $groupFactory,
        WebsiteFactory $websiteFactory,
        StoreFactory $storeFactory,
        WebsiteResourceModel $websiteResourceModel
    ) {
        $this->logger = $logger;
        $this->defaultCategoryFactory = $defaultCategoryFactory;
        $this->storeRepository = $storeRepository;
        $this->websiteRepository = $websiteRepository;
        $this->groupFactory = $groupFactory;
        $this->websiteFactory = $websiteFactory;
        $this->groupRepository = $groupRepository;
        $this->storeFactory = $storeFactory;
        $this->websiteResourceModel = $websiteResourceModel;
    }

    /**
     * Return default website code
     *
     * @return string
     */
    private function getDefaultWebsiteCode(): string
    {
        return self::UA_WEBSITE;
    }

    /**
     * Main apply function
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function apply(): void
    {

        //create websites
        $this->createWebsites();

        //create store groups
        $this->createStoreGroups();

        //Update default store group for websites
        foreach ($this->websiteDefaultStoreGroup as $websiteCode => $defaultGroupCode) {
            $websiteData = $this->getWebsiteByCode($websiteCode);
            $storeGroupData = $this->getGroupByCode($defaultGroupCode);
            if ($websiteData && $storeGroupData) {
                try {
                    /** @var Website $website */
                    $website = $this->websiteRepository->get($websiteCode);
                    $website->setDefaultGroupId($storeGroupData['group_id']);
                    $website->save();
                } catch (NoSuchEntityException $noSuchEntityException) {
                    $this->logger->error($noSuchEntityException->getMessage());
                } catch (\Exception $websiteException) {
                    $this->logger->error($websiteException->getMessage());
                }

            }
        }

        //create store views
        $this->createStoreViews();

        //Update default store id for store groups
        foreach ($this->storeGroupDefaultStoreView as $storeGroupCode => $defaultStoreViewCode) {
            $storeGroupData = $this->getGroupByCode($storeGroupCode);
            $storeViewData = $this->getStoreByCode($defaultStoreViewCode);
            if ($storeGroupData && $storeViewData) {
                /** @var Group $group */
                $group = $this->groupFactory->create();
                $group->load($storeGroupCode, 'code');
                if ($group && $group->getId() > Store::DEFAULT_STORE_ID) {
                    try {
                        $group->setDefaultStoreId($storeViewData['store_id']);
                        $group->save();
                    } catch (\Exception $storeGroupException) {
                        $this->logger->error($storeGroupException->getMessage());
                    }

                }
            }
        }
    }

    /**
     * Creates store views
     *
     * @return void
     */
    protected function createStoreViews(): void
    {
        foreach ($this->storeViews as $storeGroupCode => $storeViews) {
            $storeGroupData = $this->getGroupByCode($storeGroupCode);
            foreach ($storeViews as $storeCode => $storeTitle) {
                if (!$this->getStoreByCode($storeCode)) {
                    /** @var Store $store */
                    $store = $this->storeFactory->create();
                    $store->setCode($storeCode);
                    $store->setWebsiteId($storeGroupData['website_id']);
                    $store->setGroupId($storeGroupData['group_id']);
                    $store->setName($storeTitle);
                    $store->setSortOrder(0);
                    $store->setIsActive(1);
                    try {
                        $store->save();
                    } catch (\Exception $storeException) {
                        $this->logger->error($storeException->getMessage());
                    }

                }
            }
        }
    }

    /**
     * Creates store groups
     *
     * @return void
     */
    protected function createStoreGroups(): void
    {
        foreach ($this->storeGroups as $websiteCode => $storeGroups) {
            $websiteData = $this->getWebsiteByCode($websiteCode);
            foreach ($storeGroups as $groupCode => $groupTitle) {
                if (!$this->getGroupByCode($groupCode)) {
                    /** @var Group $group */
                    $group = $this->groupFactory->create();
                    $group->setWebsiteId($websiteData['website_id']);
                    $group->setName($groupTitle);
                    $group->setRootCategoryId($this->getDefaultCategory()->getId());
                    $group->setDefaultStoreId(Store::DEFAULT_STORE_ID);
                    $group->setCode($groupCode);
                    try {
                        $group->save();
                    } catch (\Exception $storeGroupException) {
                        $this->logger->error($storeGroupException->getMessage());
                    }

                }
            }
        }
    }

    /**
     * Create websites
     *
     * @return void
     */
    protected function createWebsites(): void
    {
        foreach ($this->websites as $websiteCode => $websiteTitle) {
            if (!$this->getWebsiteByCode($websiteCode)) {
                $isDefault = $this->getDefaultWebsiteCode() === $websiteCode ? 1 : 0;

                /** @var Website $website */
                $website = $this->websiteFactory->create();
                $website->setCode($websiteCode);
                $website->setName($websiteTitle);
                $website->setSortOrder(0);
                $website->setDefaultGroupId(Store::DEFAULT_STORE_ID);
                $website->setIsDefault($isDefault);
                try {
                    $website->save();
                } catch (\Exception $websiteException) {
                    $this->logger->error($websiteException->getMessage());
                }

            }
        }
    }

    /**
     * Get website data by code
     *
     * @param string $websiteCode
     * @return null|string[]
     */
    private function getWebsiteByCode(string $websiteCode): ?array
    {
        try {
            /** @var Website $website */
            $website = $this->websiteRepository->get($websiteCode);
            $websiteData = $website->toArray();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $websiteData = null;
        }
        return $websiteData;
    }

    /**
     * Get store group data by code
     *
     * @param string $groupCode
     * @return null|string[]
     */
    private function getGroupByCode(string $groupCode): ?array
    {
        /** @var Group $group */
        $group = $this->groupFactory->create();
        $group->load($groupCode, 'code');
        if ($group->getCode() == $groupCode) {
            $groupData = $group->toArray();
        } else {
            $this->logger->alert('Could not retrieve store group code', [$groupCode]);
            $groupData = null;
        }
        return $groupData;
    }

    /**
     * Get store data by code
     *
     * @param string $storeCode
     * @return null|string[]
     */
    private function getStoreByCode(string $storeCode): ?array
    {
        try {
            /** @var Store $store */
            $store = $this->storeRepository->get($storeCode);
            $storeData = $store->toArray();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $storeData = null;
        }
        return $storeData;
    }

    /**
     * Get default category.
     *
     * @return DefaultCategory
     */
    private function getDefaultCategory(): DefaultCategory
    {
        if ($this->defaultCategory === null) {
            $this->defaultCategory = $this->defaultCategoryFactory->create();
        }
        return $this->defaultCategory;
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
