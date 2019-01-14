<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
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
class NotifyOnsiteAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use CheckRequestOnsiteTrait;

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

        // If model has already been updated by Capture, nothing more to do here
        if (!isset($model['status'])) {
            $httpRequest = new GetHttpRequest();
            $this->gateway->execute($httpRequest);

            try {
                $this->updateModelFromRequest($model, $httpRequest);
            } catch (LogicException $e) {
                throw new HttpResponse($e->getMessage(), 400);
            }
        }

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
