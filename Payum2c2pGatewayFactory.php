<?php

namespace Villafinder\Payum2c2p;

use Villafinder\Payum2c2p\Action\ConvertPaymentAction;
use Villafinder\Payum2c2p\Action\CaptureAction;
use Villafinder\Payum2c2p\Action\NotifyAction;
use Villafinder\Payum2c2p\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class Payum2c2pGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name'           => '2c2p',
            'payum.factory_title'          => '2C2P',
            'payum.action.capture'         => new CaptureAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
