<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Villafinder\Payum2c2p\Api;

class StatusAction implements ActionInterface
{
    /**
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (!isset($model['payment_status'])) {
            $request->markNew();
        } elseif (Api::STATUS_SUCCESS === $model['payment_status']) {
            $request->markCaptured();
        } elseif (Api::STATUS_PENDING === $model['payment_status']) {
            $request->markPending();
        } elseif (Api::STATUS_CANCEL === $model['payment_status']) {
            $request->markCanceled();
        } elseif (Api::STATUS_REJECTED === $model['payment_status']) {
            $request->markFailed();
        } else {
            $request->markUnknown();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
