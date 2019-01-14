<?php

namespace Villafinder\Payum2c2p\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusOnsiteAction implements ActionInterface
{
    const STATUS_APPROVED = ['A', 'S'];
    const STATUS_FAILED   = ['PF', 'AR', 'CBR', 'FF', 'ROE', 'IP', 'F', 'RR'];
    const STATUS_REFUNDED = ['RF'];
    const STATUS_VOIDED   = ['V'];

    /**
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (!isset($model['status'])) {
            $request->markNew();
        } elseif (in_array($model['status'], self::STATUS_APPROVED)) {
            $request->markCaptured();
        } elseif (in_array($model['status'], self::STATUS_FAILED)) {
            $request->markFailed();
        } elseif (in_array($model['status'], self::STATUS_REFUNDED)) {
            $request->markRefunded();
        } elseif (in_array($model['status'], self::STATUS_VOIDED)) {
            $request->markCanceled();
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
