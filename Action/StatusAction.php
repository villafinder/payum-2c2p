<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    const STATUS_SUCCESS  = '000';
    const STATUS_PENDING  = '001';
    const STATUS_REJECTED = '002';
    const STATUS_CANCEL   = '003';
    const STATUS_ERROR    = '999';

    /**
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (!isset($model['payment_status'])) {
            $request->markNew();
        } elseif (self::STATUS_SUCCESS === $model['payment_status']) {
            $request->markCaptured();
        } elseif (self::STATUS_PENDING === $model['payment_status']) {
            $request->markPending();
        } elseif (self::STATUS_CANCEL === $model['payment_status']) {
            $request->markCanceled();
        } elseif (self::STATUS_REJECTED === $model['payment_status']) {
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
