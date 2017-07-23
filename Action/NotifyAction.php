<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Villafinder\Payum2c2p\Api;

/**
 * @property Api $api
 */
class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        if ('POST' !== $httpRequest->method) {
            throw new HttpResponse('Notification is invalid. Code 1', 400);
        }

        if (!$this->api->checkResponseHash($httpRequest->request, $model['currency'])) {
            throw new HttpResponse('Notification is invalid. Code 2', 400);
        }

        // We check payment_status because in case of error (999), 2C2P can sometimes omit the amount in its response
        if (Api::STATUS_ERROR !== $httpRequest->request['payment_status'] && $model['amount'] != $httpRequest->request['amount']) {
            throw new HttpResponse('Notification is invalid. Code 3', 400);
        }

        $model->replace($httpRequest->request);

        throw new HttpResponse('OK', 200);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
