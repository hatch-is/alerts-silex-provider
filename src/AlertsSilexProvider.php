<?php

namespace Alerts;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Class AlertsSilexProvider
 *
 * @package Alerts
 */
class AlertsSilexProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['hatch-is.alerts.processor'] = $app->share(
            function () use ($app) {
                $filter = new Filter();
                return new Processor(
                    $app['hatch-is.alerts.endpoint'],
                    $filter
                );
            }
        );
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {

    }
}
