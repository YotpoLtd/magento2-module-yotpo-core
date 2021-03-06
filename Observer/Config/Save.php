<?php

namespace Yotpo\Core\Observer\Config;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Core\Model\Api\Token as YotpoApi;
use Yotpo\Reviews\Model\Config as YotpoConfig;

/**
 * Class Save
 * Class for app-key validation
 */
class Save implements ObserverInterface
{
    /**
     * @var ReinitableConfigInterface
     */
    private $appConfig;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var YotpoConfig
     */
    private $yotpoConfig;

    /**
     * @var YotpoApi
     */
    private $yotpoApi;

    /**
     * Save constructor.
     * @param TypeListInterface $cacheTypeList
     * @param ReinitableConfigInterface $config
     * @param YotpoConfig $yotpoConfig
     * @param YotpoApi $yotpoApi
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $config,
        YotpoConfig $yotpoConfig,
        YotpoApi $yotpoApi
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->appConfig = $config;
        $this->yotpoConfig = $yotpoConfig;
        $this->yotpoApi = $yotpoApi;
    }

    /**
     * @param Observer $observer
     * @return bool|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $changedPaths = (array)$observer->getEvent()->getChangedPaths();
        if ($changedPaths) {
            $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
            $this->appConfig->reinit();

            $scope = $scopes = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            if (($scopeId = $observer->getEvent()->getStore())) {
                $scope = ScopeInterface::SCOPE_STORE;
                $scopes = ScopeInterface::SCOPE_STORES;
            } elseif (($scopeId = $observer->getEvent()->getWebsite())) {
                $scope = ScopeInterface::SCOPE_WEBSITE;
                $scopes = ScopeInterface::SCOPE_WEBSITES;
            } else {
                $scopeId = 0;
            }

            $appKey = $this->yotpoConfig->getAppKey($scopeId, $scope);

            if ($scope !== ScopeInterface::SCOPE_STORE && !$this->yotpoConfig->isSingleStoreMode()) {
                $this->resetStoreCredentials($scopeId, $scopes);
                return true;
            }

            //Check if appKey is unique:
            if ($appKey) {
                foreach ((array) $this->yotpoConfig->getAllStoreIds() as $key => $storeId) {
                    if (($scopeId && $storeId !== $scopeId) && $this->yotpoConfig->getAppKey($storeId) === $appKey) {
                        $this->resetStoreCredentials($scopeId, $scopes);
                        throw new AlreadyExistsException(__(
                            "The APP KEY you've entered is already in use by another store on this system.
                            Note that Yotpo requires a unique set of APP KEY & SECRET for each store."
                        ));
                    }
                }
            }

            if ($this->yotpoConfig->isEnabled($scopeId, $scope) &&
                !($this->yotpoApi->createAuthToken($scopeId, $scope))) {
                $this->resetStoreCredentials($scopeId, $scopes);
                throw new AlreadyExistsException(__(
                    "Please make sure the APP KEY and SECRET you've entered are correct"
                ));
            }
        }
    }

    /**
     * Reset store credentials
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function resetStoreCredentials($scopeId = null, $scope = ScopeInterface::SCOPE_STORES)
    {
        $this->yotpoConfig->resetStoreCredentials($scopeId, $scope);
        $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
        $this->appConfig->reinit();
    }
}
