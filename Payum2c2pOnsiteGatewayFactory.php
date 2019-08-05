<?php

namespace Villafinder\Payum2c2p;

use Villafinder\Payum2c2p\Action\CaptureOnsiteUnsafeAction;
use Villafinder\Payum2c2p\Action\ConvertPaymentAction;
use Villafinder\Payum2c2p\Action\CaptureOnsiteAction;
use Villafinder\Payum2c2p\Action\NotifyAction;
use Villafinder\Payum2c2p\Action\NotifyOnsiteAction;
use Villafinder\Payum2c2p\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Villafinder\Payum2c2p\Action\StatusOnsiteAction;

class Payum2c2pOnsiteGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name'           => '2c2p_onsite',
            'payum.factory_title'          => '2C2P On-Site',
            'payum.action.capture'         => new CaptureOnsiteAction(),
            'payum.action.notify'          => new NotifyOnsiteAction(),
            'payum.action.status'          => new StatusOnsiteAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
    }
}
