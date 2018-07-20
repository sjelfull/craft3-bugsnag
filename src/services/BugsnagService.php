<?php
/**
 * Bugsnag plugin for Craft CMS 3.x
 *
 * Log Craft errors/exceptions to Bugsnag.
 *
 * @link      https://superbig.co
 * @copyright Copyright (c) 2017 Superbig
 */

namespace superbig\bugsnag\services;

use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\Client;
use Bugsnag\Report;
use superbig\bugsnag\Bugsnag;

use Craft;
use craft\base\Component;
use craft\elements\User;

/**
 * @author    Superbig
 * @package   Bugsnag
 * @since     2.0.0
 */
class BugsnagService extends Component
{
    // Public Methods
    // =========================================================================

    private $settings;

    /** @var Client */
    private $bugsnag;

    public function init ()
    {
        $this->settings = Bugsnag::$plugin->getSettings();

        if ( $this->isEnabled() ) {
            $this->bugsnag = Client::make($this->settings->serverApiKey);

            $this->bugsnag->registerCallback(function (/** @var Report $report */
                $report) {

                if ( !empty($this->settings->filt) ) {
                    $report->setMetaData($this->settings->metaData);
                }

                if ( !empty($this->settings->metaData) ) {
                    $report->setMetaData($this->settings->metaData);
                }

                if ( $user = Craft::$app->getUser() ) {
                    $identity = $user->getIdentity();
                    if ( $identity ) {
                        $report->setUser([
                            'id'    => $user->id,
                            'name'  => $identity->getName(),
                            'email' => $identity->email,
                        ]);
                    }
                }
            });
        }

    }

    /**
     * @param string $text
     * @param string $type
     * @param array  $metaData
     *
     * @return bool
     */
    public function breadcrumb ($text = '', $type = Breadcrumb::MANUAL_TYPE, $metaData = [])
    {
        if ( empty($text) ) {
            return false;
        }

        $this->bugsnag->leaveBreadcrumb($text, $type, $metaData);
    }

    /*
     * @return mixed
     */
    /**
     * @param $exception
     *
     * @return bool
     */
    public function handleException ($exception)
    {
        if ( !$this->isEnabled() ) {
            return true;
        }

        $this->bugsnag->notifyException($exception);
    }

    public function isEnabled ()
    {
        return $this->settings->enabled && !empty($this->settings->serverApiKey);
    }
}
