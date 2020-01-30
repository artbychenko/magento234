<?php declare(strict_types=1);

/**
 * Assigns frontend/Andronoid/Default to all store views
 *
 * @author Artem Bychenko <artbychenko@gmail.com>
 * @package Base_Store
 */

namespace Base\Store\Setup\Patch\Data;

use Psr\Log\LoggerInterface;
use Magento\Theme\Model\Theme;
use Magento\Store\Model\Store;
use Magento\Theme\Model\Config;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\View\Design\Theme\ThemeList;
use Magento\Theme\Model\Data\Design\ConfigFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Theme\Api\DesignConfigRepositoryInterface;
use Magento\Framework\View\Design\Theme\FlyweightFactory;

/**
 * Class AssignStoreViewThemes
 *
 */
class AssignStoreViewThemes implements DataPatchInterface
{
    const THEME_CODE = 'Andronoid/Default';

    /** @var LoggerInterface */
    private $logger;

    /** @var ThemeList */
    private $themeList;

    /** @var  ConfigFactory */
    private $themeConfigFactory;

    /** @var FlyweightFactory */
    private $flyweightFactory;

    /** @var StoreRepositoryInterface */
    private $storeRepository;

    /** @var DesignConfigRepositoryInterface */
    private $designConfigRepository;

    /**
     * @var Config
     */
    private $themeConfig;

    /**
     * Patch AssignStoreViewThemes Constructor
     *
     * AssignStoreViewThemes constructor.
     * @param LoggerInterface $logger
     * @param ThemeList $themeList
     * @param Config $themeConfig
     * @param ConfigFactory $themeConfigFactory
     * @param FlyweightFactory $flyweightFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param DesignConfigRepositoryInterface $designConfigRepository
     */
    public function __construct(
        LoggerInterface $logger,
        ThemeList $themeList,
        Config $themeConfig,
        ConfigFactory $themeConfigFactory,
        FlyweightFactory $flyweightFactory,
        StoreRepositoryInterface $storeRepository,
        DesignConfigRepositoryInterface $designConfigRepository
    ) {
        $this->logger = $logger;
        $this->themeList = $themeList;
        $this->themeConfig = $themeConfig;
        $this->themeConfigFactory = $themeConfigFactory;
        $this->flyweightFactory = $flyweightFactory;
        $this->storeRepository = $storeRepository;
        $this->designConfigRepository = $designConfigRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies(): array
    {
        return [InitializeStoresAndWebsites::class];
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Assign theme to all store views
     *
     * @return void
     */
    public function apply(): void
    {
        /** @var Theme $canonTheme */
        $canonTheme = $this->flyweightFactory->create(self::THEME_CODE);
        if ($this->themeList->hasTheme($canonTheme)) {
            $stores = $this->storeRepository->getList();
            $storeIds = [];
            foreach ($stores as $store) {
                /** @var Store $store */
                $storeIds[] = $store->getStoreId();
            }
            $this->themeConfig->assignToStore(
                $canonTheme,
                $storeIds
            );
        }
    }
}
