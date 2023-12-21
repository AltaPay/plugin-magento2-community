<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Helper;

use Magento\AdminNotification\Model\InboxFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Module\ModuleListInterface;

class VersionNotification implements MessageInterface
{
    const MODULE_CODE = 'SDM_Altapay';
    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @var InboxFactory
     */
    protected $inboxFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var moduleList
     */
    protected $moduleList;

    public function __construct(
        Session             $authSession,
        InboxFactory        $inboxFactory,
        RequestInterface    $request,
        ModuleListInterface $moduleList
    ) {
        $this->authSession  = $authSession;
        $this->inboxFactory = $inboxFactory;
        $this->request      = $request;
        $this->moduleList   = $moduleList;
    }

    const MESSAGE_IDENTITY = 'AltaPay extension new version message';

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::MESSAGE_IDENTITY;
    }

    /**
     * Check whether the system message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {
        try {
            $githubContent = $this->getLatestTagInformationFromGithub();

            if($githubContent){
                $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

                $this->setSessionData("AltaPayPluginVersionGithub", $githubContent);
                $title = "AltaPay Magento 2 community new version " . $githubContent['tag_name'] . " is now available.";
                $versionData[] = [
                    'severity' => self::SEVERITY_NOTICE,
                    'date_added' => $githubContent['published_at'],
                    'title' => $title,
                    'description' => $githubContent['body'],
                    'url' => $githubContent['html_url']
                ];

                $this->inboxFactory->create()->parse(array_reverse($versionData));
                if ($moduleInfo['setup_version'] != $githubContent['tag_name']) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        $githubContent = $this->getSessionData("AltaPayPluginVersionGithub");
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);
        $message = __("AltaPay Magento 2 community new version is now available.");

        if (isset($githubContent['html_url']) && isset($githubContent['tag_name'])) {
            $message .= __(
                "<a href= \"" . $githubContent['html_url'] . "\" target='_blank'> " . $githubContent['tag_name'] . "!</a>"
            );
        }

        if (isset($moduleInfo['setup_version'])) {
            $message .= __(
                " Your installed version is " . $moduleInfo['setup_version'] . ". We recommend updating your extension to the latest version."
            );
        }
        return __($message);
    }

    /**
     * Retrieve system message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    public function getLatestTagInformationFromGithub()
    {
        $data = [];

        try {
            $client = new \GuzzleHttp\Client();
            $request = new \GuzzleHttp\Psr7\Request('GET', 'https://api.github.com/repos/AltaPay/plugin-magento2-community/releases/latest');
            $res = $client->sendAsync($request)->wait();
            $body = $res->getBody();
            $data =  json_decode($body, true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return [];
        }

        return $data;
    }

    /**
     * Set the current value for the backend session
     */
    public function setSessionData($key, $value)
    {
        return $this->authSession->setData($key, $value);
    }

    /**
     * Retrieve the session value
     */
    public function getSessionData($key, $remove = false)
    {
        return $this->authSession->getData($key, $remove);
    }

    /**
     * @return bool
     */
    private function isNewVersionAvailable()
    {
        $githubContent = $this->getSessionData("AltaPayPluginVersionGithub");

        if (isset($githubContent)) {
            $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);
            return $moduleInfo['setup_version'] !== $githubContent['tag_name'];
        }
    }
}
